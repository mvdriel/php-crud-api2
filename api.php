<?php
/**
 * PHP-CRUD-API                 License: MIT
 * Maurits van der Schee: maurits@vdschee.nl
 * https://github.com/mevdschee/php-crud-api
 **/

namespace Tqd\PhpCrudApi;

// file: src/Tqd/PhpCrudApi/Cache/Cache.php

interface Cache
{
    public function set(String $key, String $value, int $ttl = 0): bool;
    public function get(String $key): String;
    public function clear(): bool;
}

// file: src/Tqd/PhpCrudApi/Cache/CacheFactory.php

class CacheFactory
{
    const PREFIX = 'phpcrudapi-%s-';

    private static function getPrefix(): String
    {
        return sprintf(self::PREFIX, substr(md5(__FILE__), 0, 8));
    }

    public static function create(Config $config): Cache
    {
        switch ($config->getCacheType()) {
            case 'TempFile':
                $cache = new TempFileCache(self::getPrefix(), $config->getCachePath());
                break;
            case 'Redis':
                $cache = new RedisCache(self::getPrefix(), $config->getCachePath());
                break;
            case 'Memcache':
                $cache = new MemcacheCache(self::getPrefix(), $config->getCachePath());
                break;
            case 'Memcached':
                $cache = new MemcachedCache(self::getPrefix(), $config->getCachePath());
                break;
            default:
                $cache = new NoCache();
        }
        return $cache;
    }
}

// file: src/Tqd/PhpCrudApi/Cache/MemcacheCache.php

class MemcacheCache implements Cache
{
    protected $prefix;
    protected $memcache;

    public function __construct(String $prefix, String $config)
    {
        $this->prefix = $prefix;
        if ($config == '') {
            $address = 'localhost';
            $port = 11211;
        } elseif (strpos($config, ':') === false) {
            $address = $config;
            $port = 11211;
        } else {
            list($address, $port) = explode(':', $config);
        }
        $this->memcache = $this->create();
        $this->memcache->addServer($address, $port);
    }

    protected function create(): object
    {
        return new \Memcache();
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, 0, $ttl);
    }

    public function get(String $key): String
    {
        return $this->memcache->get($this->prefix . $key) ?: '';
    }

    public function clear(): bool
    {
        return $this->memcache->flush();
    }
}

// file: src/Tqd/PhpCrudApi/Cache/MemcachedCache.php

class MemcacheCached extends MemcacheCache
{
    protected function create(): object
    {
        return new \Memcached();
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, $ttl);
    }
}

// file: src/Tqd/PhpCrudApi/Cache/NoCache.php

class NoCache implements Cache
{
    public function __construct()
    {
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return true;
    }

    public function get(String $key): String
    {
        return '';
    }

    public function clear(): bool
    {
        return true;
    }
}

// file: src/Tqd/PhpCrudApi/Cache/RedisCache.php

class RedisCache implements Cache
{
    protected $prefix;
    protected $redis;

    public function __construct(String $prefix, String $config)
    {
        $this->prefix = $prefix;
        if ($config == '') {
            $config = '127.0.0.1';
        }
        $params = explode(':', $config, 6);
        if (isset($params[3])) {
            $params[3] = null;
        }
        $this->redis = new \Redis();
        call_user_func_array(array($this->redis, 'pconnect'), $params);
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return $this->redis->set($this->prefix . $key, $value, $ttl);
    }

    public function get(String $key): String
    {
        return $this->redis->get($this->prefix . $key) ?: '';
    }

    public function clear(): bool
    {
        return $this->redis->flushDb();
    }
}

// file: src/Tqd/PhpCrudApi/Cache/TempFileCache.php

class TempFileCache implements Cache
{
    const SUFFIX = 'cache';

    private $path;
    private $segments;

    public function __construct(String $prefix, String $config)
    {
        $this->segments = [];
        $s = DIRECTORY_SEPARATOR;
        $ps = PATH_SEPARATOR;
        if ($config == '') {
            $id = substr(md5(__FILE__), 0, 8);
            $this->path = sys_get_temp_dir() . $s . $prefix . self::SUFFIX;
        } elseif (strpos($config, $ps) === false) {
            $this->path = $config;
        } else {
            list($path, $segments) = explode($ps, $config);
            $this->path = $path;
            $this->segments = explode(',', $segments);
        }
        if (file_exists($this->path) && is_dir($this->path)) {
            $this->clean($this->path, array_filter($this->segments), strlen(md5('')), false);
        }
    }

    private function getFileName(String $key): String
    {
        $s = DIRECTORY_SEPARATOR;
        $md5 = md5($key);
        $filename = rtrim($this->path, $s) . $s;
        $i = 0;
        foreach ($this->segments as $segment) {
            $filename .= substr($md5, $i, $segment) . $s;
            $i += $segment;
        }
        $filename .= substr($md5, $i);
        return $filename;
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        $filename = $this->getFileName($key);
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            if (!mkdir($dirname, 0755, true)) {
                return false;
            }
        }
        $string = $ttl . '|' . $value;
        return file_put_contents($filename, $string, LOCK_EX) !== false;
    }

    private function getString($filename): String
    {
        $data = file_get_contents($filename);
        if ($data === false) {
            return '';
        }
        list($ttl, $string) = explode('|', $data, 2);
        if ($ttl > 0 && time() - filemtime($filename) > $ttl) {
            return '';
        }
        return $string;
    }

    public function get(String $key): String
    {
        $filename = $this->getFileName($key);
        if (!file_exists($filename)) {
            return '';
        }
        $string = $this->getString($filename);
        if ($string == null) {
            return '';
        }
        return $string;
    }

    private function clean(String $path, array $segments, int $len, bool $all)/*: void*/
    {
        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $filename = $path . DIRECTORY_SEPARATOR . $entry;
            if (count($segments) == 0) {
                if (strlen($entry) != $len) {
                    continue;
                }
                if (is_file($filename)) {
                    if ($all || $this->getString($filename) == null) {
                        unlink($filename);
                    }
                }
            } else {
                if (strlen($entry) != $segments[0]) {
                    continue;
                }
                if (is_dir($filename)) {
                    $this->clean($filename, array_slice($segments, 1), $len - $segments[0], $all);
                    rmdir($filename);
                }
            }
        }
    }

    public function clear(): bool
    {
        if (!file_exists($this->path) || !is_dir($this->path)) {
            return false;
        }
        $this->clean($this->path, array_filter($this->segments), strlen(md5('')), true);
        return true;
    }
}

// file: src/Tqd/PhpCrudApi/Controller/CacheController.php

class CacheController
{
    private $cache;
    private $responder;

    public function __construct(Router $router, Responder $responder, Cache $cache)
    {
        $router->register('GET', '/cache/clear', array($this, 'clear'));
        $this->cache = $cache;
        $this->responder = $responder;
    }

    public function clear(Request $request): Response
    {
        return $this->responder->success($this->cache->clear());
    }

}

// file: src/Tqd/PhpCrudApi/Controller/DataController.php

class DataController
{
    private $service;
    private $responder;

    public function __construct(Router $router, Responder $responder, DataService $service)
    {
        $router->register('GET', '/data/*', array($this, '_list'));
        $router->register('POST', '/data/*', array($this, 'create'));
        $router->register('GET', '/data/*/*', array($this, 'read'));
        $router->register('PUT', '/data/*/*', array($this, 'update'));
        $router->register('DELETE', '/data/*/*', array($this, 'delete'));
        $this->service = $service;
        $this->responder = $responder;
    }

    public function _list(Request $request): Response
    {
        $table = $request->getPathSegment(2);
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        return $this->responder->success($this->service->_list($table, $params));
    }

    public function read(Request $request): Response
    {
        $table = $request->getPathSegment(2);
        $id = $request->getPathSegment(3);
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        if (strpos($id, ',') !== false) {
            $ids = explode(',', $id);
            $result = [];
            for ($i = 0; $i < count($ids); $i++) {
                array_push($result, $this->service->read($table, $ids[$i], $params));
            }
            return $this->responder->success($result);
        } else {
            $response = $this->service->read($table, $id, $params);
            if ($response === null) {
                return $this->responder->error(ErrorCode::RECORD_NOT_FOUND, $id);
            }
            return $this->responder->success($response);
        }
    }

    public function create(Request $request): Response
    {
        $table = $request->getPathSegment(2);
        $record = $request->getBody();
        if ($record === null) {
            return $this->responder->error(ErrorCode::HTTP_MESSAGE_NOT_READABLE, '');
        }
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        if (is_array($record)) {
            $result = array();
            foreach ($record as $r) {
                $result[] = $this->service->create($table, $r, $params);
            }
            return $this->responder->success($result);
        } else {
            return $this->responder->success($this->service->create($table, $record, $params));
        }
    }

    public function update(Request $request): Response
    {
        $table = $request->getPathSegment(2);
        $id = $request->getPathSegment(3);
        $record = $request->getBody();
        if ($record === null) {
            return $this->responder->error(ErrorCode::HTTP_MESSAGE_NOT_READABLE, '');
        }
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        $ids = explode(',', $id);
        if (is_array($record)) {
            if (count($ids) != count($record)) {
                return $this->responder->error(ErrorCode::ARGUMENT_COUNT_MISMATCH, $id);
            }
            $result = array();
            for ($i = 0; $i < count($ids); $i++) {
                $result[] = $this->service->update($table, $ids[$i], $record[$i], $params);
            }
            return $this->responder->success($result);
        } else {
            if (count($ids) != 1) {
                return $this->responder->error(ErrorCode::ARGUMENT_COUNT_MISMATCH, $id);
            }
            return $this->responder->success($this->service->update($table, $id, $record, $params));
        }
    }

    public function delete(Request $request): Response
    {
        $table = $request->getPathSegment(2);
        $id = $request->getPathSegment(3);
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        $ids = explode(',', $id);
        if (count($ids) > 1) {
            $result = array();
            for ($i = 0; $i < count($ids); $i++) {
                $result[] = $this->service->delete($table, $ids[$i], $params);
            }
            return $this->responder->success($result);
        } else {
            return $this->responder->success($this->service->delete($table, $id, $params));
        }
    }

}

// file: src/Tqd/PhpCrudApi/Controller/MetaController.php

class MetaController
{
    private $service;
    private $responder;

    public function __construct(Router $router, Responder $responder, MetaService $service)
    {
        $router->register('GET', '/meta', array($this, 'getDatabase'));
        $router->register('GET', '/meta/*', array($this, 'getTable'));
        $router->register('GET', '/meta/*/*', array($this, 'getColumn'));
        $this->service = $service;
        $this->responder = $responder;
    }

    public function getDatabase(Request $request): Response
    {
        $database = $this->service->getDatabase();
        return $this->responder->success($database);
    }

    public function getTable(Request $request): Response
    {
        $tableName = $request->getPathSegment(2);
        if (!$this->service->hasTable($tableName)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $tableName);
        }
        $table = $this->service->getTable($tableName);
        return $this->responder->success($table);
    }

    public function getColumn(Request $request): Response
    {
        $tableName = $request->getPathSegment(2);
        $columnName = $request->getPathSegment(3);
        if (!$this->service->hasTable($tableName)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $tableName);
        }
        $table = $this->service->getTable($tableName);
        if (!$table->exists($columnName)) {
            return $this->responder->error(ErrorCode::COLUMN_NOT_FOUND, $columnName);
        }
        $column = $table->get($columnName);
        return $this->responder->success($column);
    }

}

// file: src/Tqd/PhpCrudApi/Controller/OpenApiController.php

class OpenApiController
{
    private $openApi;
    private $responder;

    public function __construct(Router $router, Responder $responder, OpenApiService $openApi)
    {
        $router->register('GET', '/openapi', array($this, 'openapi'));
        $this->openApi = $openApi;
        $this->responder = $responder;
    }

    public function openapi(Request $request): Response
    {
        return $this->responder->success(false);
    }

}

// file: src/Tqd/PhpCrudApi/Controller/Responder.php

class Responder
{
    public function error(int $error, String $argument): Response
    {
        $errorCode = new ErrorCode($error);
        $status = $errorCode->getStatus();
        $document = new ErrorDocument($errorCode, $argument);
        return new Response($status, $document);
    }

    public function success($result): Response
    {
        return new Response(Response::OK, $result);
    }

}

// file: src/Tqd/PhpCrudApi/Data/Condition/AndCondition.php

class AndCondition extends Condition
{
    private $conditions;

    public function __construct(Condition $condition1, Condition $condition2)
    {
        $this->conditions = [$condition1, $condition2];
    }

    public function _and(Condition $condition): Condition
    {
        if ($condition instanceof NoCondition) {
            return $this;
        }
        $this->conditions[] = $condition;
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public static function fromArray(array $conditions): Condition
    {
        $condition = new NoCondition();
        foreach ($conditions as $c) {
            $condition = $condition->_and($c);
        }
        return $condition;
    }
}

// file: src/Tqd/PhpCrudApi/Data/Condition/ColumnCondition.php

class ColumnCondition extends Condition
{
    private $column;
    private $operator;
    private $value;

    public function __construct(ReflectedColumn $column, String $operator, String $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getColumn(): ReflectedColumn
    {
        return $this->column;
    }

    public function getOperator(): String
    {
        return $this->operator;
    }

    public function getValue(): String
    {
        return $this->value;
    }
}

// file: src/Tqd/PhpCrudApi/Data/Condition/Condition.php

abstract class Condition
{
    public function _and(Condition $condition): Condition
    {
        if ($condition instanceof NoCondition) {
            return $this;
        }
        return new AndCondition($this, $condition);
    }

    public function _or(Condition $condition): Condition
    {
        if ($condition instanceof NoCondition) {
            return $this;
        }
        return new OrCondition($this, $condition);
    }

    public function _not(): Condition
    {
        return new NotCondition($this);
    }

    public static function fromString(ReflectedTable $table, String $value): Condition
    {
        $condition = new NoCondition();
        $parts = explode(',', $value, 3);
        if (count($parts) < 2) {
            return null;
        }
        $field = $table->get($parts[0]);
        $command = $parts[1];
        $negate = false;
        $spatial = false;
        if (strlen($command) > 2) {
            if (substr($command, 0, 1) == 'n') {
                $negate = true;
                $command = substr($command, 1);
            }
            if (substr($command, 0, 1) == 's') {
                $spatial = true;
                $command = substr($command, 1);
            }
        }
        if (count($parts) == 3 || (count($parts) == 2 && in_array($command, ['ic', 'is', 'iv']))) {
            if ($spatial) {
                if (in_array($command, ['co', 'cr', 'di', 'eq', 'in', 'ov', 'to', 'wi', 'ic', 'is', 'iv'])) {
                    $condition = new SpatialCondition($field, $command, $parts[2]);
                }
            } else {
                if (in_array($command, ['cs', 'sw', 'ew', 'eq', 'lt', 'le', 'ge', 'gt', 'bt', 'in', 'is'])) {
                    $condition = new ColumnCondition($field, $command, $parts[2]);
                }
            }
        }
        if ($negate) {
            $condition = $condition->_not();
        }
        return $condition;
    }

}

// file: src/Tqd/PhpCrudApi/Data/Condition/NoCondition.php

class NoCondition extends Condition
{
    public function _and(Condition $condition): Condition
    {
        return $condition;
    }

    public function _or(Condition $condition): Condition
    {
        return $condition;
    }

    public function not(): Condition
    {
        return $this;
    }

}

// file: src/Tqd/PhpCrudApi/Data/Condition/NotCondition.php

class NotCondition extends Condition
{
    private $condition;

    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }

    public function getCondition(): Condition
    {
        return $this->condition;
    }
}

// file: src/Tqd/PhpCrudApi/Data/Condition/OrCondition.php

class OrCondition extends Condition
{
    private $conditions;

    public function __construct(Condition $condition1, Condition $condition2)
    {
        $this->conditions = [$condition1, $condition2];
    }

    public function _or(Condition $condition): Condition
    {
        if ($condition instanceof NoCondition) {
            return $this;
        }
        $this->conditions[] = $condition;
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public static function fromArray(array $conditions): Condition
    {
        $condition = new NoCondition();
        foreach ($conditions as $c) {
            $condition = $condition->_or($c);
        }
        return $condition;
    }
}

// file: src/Tqd/PhpCrudApi/Data/Condition/SpatialCondition.php

class SpatialCondition extends ColumnCondition
{
}

// file: src/Tqd/PhpCrudApi/Data/Record/ErrorDocument.php

class ErrorDocument
{

    public $code;

    public $message;

    public function __construct(ErrorCode $errorCode, String $argument)
    {
        $this->code = $errorCode->getCode();
        $this->message = $errorCode->getMessage($argument);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMessage(): String
    {
        return $this->message;
    }

}

// file: src/Tqd/PhpCrudApi/Data/Record/ListResponse.php

class ListResponse implements \JsonSerializable
{

    private $records;

    private $results;

    public function __construct(array $records, int $results)
    {
        $this->records = $records;
        $this->results = $results;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getResults(): int
    {
        return $this->results;
    }

    public function jsonSerialize()
    {
        $result = ['records' => $this->records];
        if ($this->results) {
            $result['results'] = $this->results;
        }
        return $result;
    }
}

// file: src/Tqd/PhpCrudApi/Data/ColumnSelector.php

class ColumnSelector
{

    private function isMandatory(String $tableName, String $columnName, array $params): bool
    {
        return isset($params['mandatory']) && in_array($tableName . "." . $columnName, $params['mandatory']);
    }

    private function select(String $tableName, bool $primaryTable, array $params, String $paramName,
        array $columnNames, bool $include): array{
        if (!isset($params[$paramName])) {
            return $columnNames;
        }
        $columns = array();
        foreach (explode(',', $params[$paramName][0]) as $columnName) {
            $columns[$columnName] = true;
        }
        $result = array();
        foreach ($columnNames as $columnName) {
            $match = isset($columns['*.*']);
            if (!$match) {
                $match = isset($columns[$tableName . '.*']) || isset($columns[$tableName . '.' . $columnName]);
            }
            if ($primaryTable && !$match) {
                $match = isset($columns['*']) || isset($columns[$columnName]);
            }
            if ($match) {
                if ($include || $this->isMandatory($tableName, $columnName, $params)) {
                    $result[] = $columnName;
                }
            } else {
                if (!$include || $this->isMandatory($tableName, $columnName, $params)) {
                    $result[] = $columnName;
                }
            }
        }
        return $result;
    }

    public function getNames(ReflectedTable $table, bool $primaryTable, array $params): array
    {
        $tableName = $table->getName();
        $results = $table->columnNames();
        $results = $this->select($tableName, $primaryTable, $params, 'columns', $results, true);
        $results = $this->select($tableName, $primaryTable, $params, 'exclude', $results, false);
        return $results;
    }

    public function getValues(ReflectedTable $table, bool $primaryTable, /* object */$record, array $params): array
    {
        $results = array();
        $columnNames = $this->getNames($table, $primaryTable, $params);
        foreach ($columnNames as $columnName) {
            if (property_exists($record, $columnName)) {
                $results[$columnName] = $record->$columnName;
            }
        }
        return $results;
    }

}

// file: src/Tqd/PhpCrudApi/Data/DataService.php

class DataService
{
    private $db;
    private $tables;
    private $columns;
    private $includer;
    private $filters;
    private $ordering;
    private $pagination;

    public function __construct(GenericDB $db, MetaService $meta)
    {
        $this->db = $db;
        $this->tables = $meta->getDatabase();
        $this->columns = new ColumnSelector();
        $this->includer = new RelationIncluder($this->columns);
        $this->filters = new FilterInfo();
        $this->ordering = new OrderingInfo();
        $this->pagination = new PaginationInfo();
    }

    private function sanitizeRecord(String $tableName, /* object */$record, String $id)
    {
        $keyset = array_keys((array) $record);
        foreach ($keyset as $key) {
            if (!$this->tables->get($tableName)->exists($key)) {
                unset($record[$key]);
            }
        }
        if ($id != '') {
            $pk = $this->tables->get($tableName)->getPk();
            foreach ($this->tables->get($tableName)->columnNames() as $key) {
                $field = $this->tables->get($tableName)->get($key);
                if ($field->getName() == $pk->getName()) {
                    unset($record->$key);
                }
            }
        }
    }

    public function exists(String $table): bool
    {
        return $this->tables->exists($table);
    }

    public function create(String $tableName, /* object */$record, array $params)
    {
        $this->sanitizeRecord($tableName, $record, '');
        $table = $this->tables->get($tableName);
        $columnValues = $this->columns->getValues($table, true, $record, $params);
        return $this->db->createSingle($table, $columnValues);
    }

    public function read(String $tableName, String $id, array $params) /*: ?object*/
    {
        $table = $this->tables->get($tableName);
        $this->includer->addMandatoryColumns($table, $this->tables, $params);
        $columnNames = $this->columns->getNames($table, true, $params);
        $record = $this->db->selectSingle($table, $columnNames, $id);
        if ($record == null) {
            return null;
        }
        $records = array($record);
        $this->includer->addIncludes($table, $records, $this->tables, $params, $this->db);
        return $records[0];
    }

    public function update(String $tableName, String $id, /* object */$record, array $params)
    {
        $this->sanitizeRecord($tableName, $record, $id);
        $table = $this->tables->get($tableName);
        $columnValues = $this->columns->getValues($table, true, $record, $params);
        return $this->db->updateSingle($table, $columnValues, $id);
    }

    public function delete(String $tableName, String $id, array $params)
    {
        $table = $this->tables->get($tableName);
        return $this->db->deleteSingle($table, $id);
    }

    public function _list(String $tableName, array $params): ListResponse
    {
        $table = $this->tables->get($tableName);
        $this->includer->addMandatoryColumns($table, $this->tables, $params);
        $columnNames = $this->columns->getNames($table, true, $params);
        $condition = $this->filters->getCombinedConditions($table, $params);
        $columnOrdering = $this->ordering->getColumnOrdering($table, $params);
        if (!$this->pagination->hasPage($params)) {
            $offset = 0;
            $limit = $this->pagination->getResultSize($params);
            $count = 0;
        } else {
            $offset = $this->pagination->getPageOffset($params);
            $limit = $this->pagination->getPageSize($params);
            $count = $this->db->selectCount($table, $condition);
        }
        $records = $this->db->selectAll($table, $columnNames, $condition, $columnOrdering, $offset, $limit);
        $this->includer->addIncludes($table, $records, $this->tables, $params, $this->db);
        return new ListResponse($records, $count);
    }
}

// file: src/Tqd/PhpCrudApi/Data/ErrorCode.php

class ErrorCode
{

    private $code;
    private $message;
    private $status;

    const ERROR_NOT_FOUND = 9999;
    const ROUTE_NOT_FOUND = 1000;
    const TABLE_NOT_FOUND = 1001;
    const ARGUMENT_COUNT_MISMATCH = 1002;
    const RECORD_NOT_FOUND = 1003;
    const ORIGIN_FORBIDDEN = 1004;
    const COLUMN_NOT_FOUND = 1005;
    const HTTP_MESSAGE_NOT_READABLE = 1008;
    const DUPLICATE_KEY_EXCEPTION = 1009;
    const DATA_INTEGRITY_VIOLATION = 1010;

    private $values = [
        9999 => ["%s", Response::INTERNAL_SERVER_ERROR],
        1000 => ["Route '%s' not found", Response::NOT_FOUND],
        1001 => ["Table '%s' not found", Response::NOT_FOUND],
        1002 => ["Argument count mismatch in '%s'", Response::NOT_ACCEPTABLE],
        1003 => ["Record '%s' not found", Response::NOT_FOUND],
        1004 => ["Origin '%s' is forbidden", Response::FORBIDDEN],
        1005 => ["Column '%s' not found", Response::NOT_FOUND],
        1008 => ["Cannot read HTTP message", Response::NOT_ACCEPTABLE],
        1009 => ["Duplicate key exception", Response::NOT_ACCEPTABLE],
        1010 => ["Data integrity violation", Response::NOT_ACCEPTABLE],
    ];

    public function __construct(int $code)
    {
        if (!isset($this->values[$code])) {
            $code = 9999;
        }
        $this->code = $code;
        $this->message = $this->values[$code][0];
        $this->status = $this->values[$code][1];
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMessage(String $argument): String
    {
        return sprintf($this->message, $argument);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

}

// file: src/Tqd/PhpCrudApi/Data/FilterInfo.php

class FilterInfo
{

    private function addConditionFromFilterPath(PathTree $conditions, array $path, ReflectedTable $table, array $params)
    {
        $key = 'filter' . implode('', $path);
        if (isset($params[$key])) {
            foreach ($params[$key] as $filter) {
                $condition = Condition::fromString($table, $filter);
                if ($condition != null) {
                    $conditions->put($path, $condition);
                }
            }
        }
    }

    private function getConditionsAsPathTree(ReflectedTable $table, array $params): PathTree
    {
        $conditions = new PathTree();
        $this->addConditionFromFilterPath($conditions, [], $table, $params);
        for ($n = ord('0'); $n <= ord('9'); $n++) {
            $this->addConditionFromFilterPath($conditions, [chr($n)], $table, $params);
            for ($l = ord('a'); $l <= ord('f'); $l++) {
                $this->addConditionFromFilterPath($conditions, [chr($n), chr($l)], $table, $params);
            }
        }
        return $conditions;
    }

    private function combinePathTreeOfConditions(PathTree $tree): Condition
    {
        $andConditions = $tree->getValues();
        $and = AndCondition::fromArray($andConditions);
        $orConditions = [];
        foreach ($tree->getKeys() as $p) {
            $orConditions[] = $this->combinePathTreeOfConditions($tree->get($p));
        }
        $or = OrCondition::fromArray($orConditions);
        return $and->_and($or);
    }

    public function getCombinedConditions(ReflectedTable $table, array $params): Condition
    {
        return $this->combinePathTreeOfConditions($this->getConditionsAsPathTree($table, $params));
    }

}

// file: src/Tqd/PhpCrudApi/Data/HabtmValues.php

class HabtmValues
{
    public $pkValues;
    public $fkValues;

    public function __construct(array $pkValues, array $fkValues)
    {
        $this->pkValues = $pkValues;
        $this->fkValues = $fkValues;
    }
}

// file: src/Tqd/PhpCrudApi/Data/OrderingInfo.php

class OrderingInfo
{

    public function getColumnOrdering(ReflectedTable $table, array $params): array
    {
        $fields = array();
        if (isset($params['order'])) {
            foreach ($params['order'] as $order) {
                $parts = explode(',', $order, 3);
                $columnName = $parts[0];
                if (!$table->exists($columnName)) {
                    continue;
                }
                $ascending = 'ASC';
                if (count($parts) > 1) {
                    if (substr(strtoupper($parts[1]), 0, 4) == "DESC") {
                        $ascending = 'DESC';
                    }
                }
                $fields[] = [$columnName, $ascending];
            }
        }
        if (count($fields) == 0) {
            $fields[] = [$table->getPk()->getName(), 'ASC'];
        }
        return $fields;
    }
}

// file: src/Tqd/PhpCrudApi/Data/PaginationInfo.php

class PaginationInfo
{

    public $DEFAULT_PAGE_SIZE = 20;

    public function hasPage(array $params): bool
    {
        return isset($params['page']);
    }

    public function getPageOffset(array $params): int
    {
        $offset = 0;
        $pageSize = $this->getPageSize($params);
        if (isset($params['page'])) {
            foreach ($params['page'] as $page) {
                $parts = explode(',', $page, 2);
                $page = intval($parts[0]) - 1;
                $offset = $page * $pageSize;
            }
        }
        return $offset;
    }

    public function getPageSize(array $params): int
    {
        $pageSize = $this->DEFAULT_PAGE_SIZE;
        if (isset($params['page'])) {
            foreach ($params['page'] as $page) {
                $parts = explode(',', $page, 2);
                if (count($parts) > 1) {
                    $pageSize = intval($parts[1]);
                }
            }
        }
        return $pageSize;
    }

    public function getResultSize(array $params): int
    {
        $numberOfRows = -1;
        if (isset($params['size'])) {
            foreach ($params['size'] as $size) {
                $numberOfRows = intval($size);
            }
        }
        return $numberOfRows;
    }

}

// file: src/Tqd/PhpCrudApi/Data/PathTree.php

class PathTree
{

    private $values = array();

    private $branches = array();

    public function getValues(): array
    {
        return $this->values;
    }

    public function put(array $path, $value)
    {
        if (count($path) == 0) {
            $this->values[] = $value;
            return;
        }
        $key = array_shift($path);
        if (!isset($this->branches[$key])) {
            $this->branches[$key] = new PathTree();
        }
        $tree = $this->branches[$key];
        $tree->put($path, $value);
    }

    public function getKeys(): array
    {
        return array_keys($this->branches);
    }

    public function has($key): bool
    {
        return isset($this->branches[$key]);
    }

    public function get($key): PathTree
    {
        return $this->branches[$key];
    }
}

// file: src/Tqd/PhpCrudApi/Data/RelationIncluder.php

class RelationIncluder
{

    private $columns;

    public function __construct(ColumnSelector $columns)
    {
        $this->columns = $columns;
    }

    public function addMandatoryColumns(ReflectedTable $table, ReflectedDatabase $tables, array &$params)/*: void*/
    {
        if (!isset($params['include']) || !isset($params['columns'])) {
            return;
        }
        $params['mandatory'] = array();
        foreach ($params['include'] as $tableNames) {
            $t1 = $table;
            foreach (explode(',', $tableNames) as $tableName) {
                if (!$tables->exists($tableName)) {
                    continue;
                }
                $t2 = $tables->get($tableName);
                $fks1 = $t1->getFksTo($t2->getName());
                $t3 = $this->hasAndBelongsToMany($t1, $t2, $tables);
                if ($t3 != null || count($fks1) > 0) {
                    $params['mandatory'][] = $t2->getName() . '.' . $t2->getPk()->getName();
                }
                foreach ($fks1 as $fk) {
                    $params['mandatory'][] = $t1->getName() . '.' . $fk->getName();
                }
                $fks2 = $t2->getFksTo($t1->getName());
                if ($t3 != null || count($fks2) > 0) {
                    $params['mandatory'][] = $t1->getName() . '.' . $t1->getPk()->getName();
                }
                foreach ($fks2 as $fk) {
                    $params['mandatory'][] = $t2->getName() . '.' . $fk->getName();
                }
                $t1 = $t2;
            }
        }
    }

    private function getIncludesAsPathTree(ReflectedDatabase $tables, array $params): PathTree
    {
        $includes = new PathTree();
        if (isset($params['include'])) {
            foreach ($params['include'] as $tableNames) {
                $path = array();
                foreach (explode(',', $tableNames) as $tableName) {
                    $t = $tables->get($tableName);
                    if ($t != null) {
                        $path[] = $t->getName();
                    }
                }
                $includes->put($path, true);
            }
        }
        return $includes;
    }

    public function addIncludes(ReflectedTable $table, array &$records, ReflectedDatabase $tables, array $params,
        GenericDB $db)/*: void*/{

        $includes = $this->getIncludesAsPathTree($tables, $params);
        $this->addIncludesForTables($table, $includes, $records, $tables, $params, $db);
    }

    private function hasAndBelongsToMany(ReflectedTable $t1, ReflectedTable $t2, ReflectedDatabase $tables) /*: ?ReflectedTable*/
    {
        foreach ($tables->getTableNames() as $tableName) {
            $t3 = $tables->get($tableName);
            if (count($t3->getFksTo($t1->getName())) > 0 && count($t3->getFksTo($t2->getName())) > 0) {
                return $t3;
            }
        }
        return null;
    }

    private function addIncludesForTables(ReflectedTable $t1, PathTree $includes, array &$records,
        ReflectedDatabase $tables, array $params, GenericDB $db) {

        foreach ($includes->getKeys() as $t2Name) {

            $t2 = $tables->get($t2Name);

            $belongsTo = count($t1->getFksTo($t2->getName())) > 0;
            $hasMany = count($t2->getFksTo($t1->getName())) > 0;
            $t3 = $this->hasAndBelongsToMany($t1, $t2, $tables);
            $hasAndBelongsToMany = ($t3 != null);

            $newRecords = array();
            $fkValues = null;
            $pkValues = null;
            $habtmValues = null;

            if ($belongsTo) {
                $fkValues = $this->getFkEmptyValues($t1, $t2, $records);
                $this->addFkRecords($t2, $fkValues, $params, $db, $newRecords);
            }
            if ($hasMany) {
                $pkValues = $this->getPkEmptyValues($t1, $records);
                $this->addPkRecords($t1, $t2, $pkValues, $params, $db, $newRecords);
            }
            if ($hasAndBelongsToMany) {
                $habtmValues = $this->getHabtmEmptyValues($t1, $t2, $t3, $db, $records);
                $this->addFkRecords($t2, $habtmValues->fkValues, $params, $db, $newRecords);
            }

            $this->addIncludesForTables($t2, $includes->get($t2Name), $newRecords, $tables, $params, $db);

            if ($fkValues != null) {
                $this->fillFkValues($t2, $newRecords, $fkValues);
                $this->setFkValues($t1, $t2, $records, $fkValues);
            }
            if ($pkValues != null) {
                $this->fillPkValues($t1, $t2, $newRecords, $pkValues);
                $this->setPkValues($t1, $t2, $records, $pkValues);
            }
            if ($habtmValues != null) {
                $this->fillFkValues($t2, $newRecords, $habtmValues->fkValues);
                $this->setHabtmValues($t1, $t3, $records, $habtmValues);
            }
        }
    }

    private function getFkEmptyValues(ReflectedTable $t1, ReflectedTable $t2, array $records): array
    {
        $fkValues = array();
        $fks = $t1->getFksTo($t2->getName());
        foreach ($fks as $fk) {
            $fkName = $fk->getName();
            foreach ($records as $record) {
                if (isset($record[$fkName])) {
                    $fkValue = $record[$fkName];
                    $fkValues[$fkValue] = null;
                }
            }
        }
        return $fkValues;
    }

    private function addFkRecords(ReflectedTable $t2, array $fkValues, array $params, GenericDB $db, array &$records)/*: void*/
    {
        $pk = $t2->getPk();
        $columnNames = $this->columns->getNames($t2, false, $params);
        $fkIds = array_keys($fkValues);

        foreach ($db->selectMultiple($t2, $columnNames, $fkIds) as $record) {
            $records[] = $record;
        }
    }

    private function fillFkValues(ReflectedTable $t2, array $fkRecords, array &$fkValues)/*: void*/
    {
        $pkName = $t2->getPk()->getName();
        foreach ($fkRecords as $fkRecord) {
            $pkValue = $fkRecord[$pkName];
            $fkValues[$pkValue] = $fkRecord;
        }
    }

    private function setFkValues(ReflectedTable $t1, ReflectedTable $t2, array &$records, array $fkValues)/*: void*/
    {
        $fks = $t1->getFksTo($t2->getName());
        foreach ($fks as $fk) {
            $fkName = $fk->getName();
            foreach ($records as $i => $record) {
                if (isset($record[$fkName])) {
                    $key = $record[$fkName];
                    $records[$i][$fkName] = $fkValues[$key];
                }
            }
        }
    }

    private function getPkEmptyValues(ReflectedTable $t1, array $records): array
    {
        $pkValues = array();
        $pkName = $t1->getPk()->getName();
        foreach ($records as $record) {
            $key = $record[$pkName];
            $pkValues[$key] = array();
        }
        return $pkValues;
    }

    private function addPkRecords(ReflectedTable $t1, ReflectedTable $t2, array $pkValues, array $params, GenericDB $db, array &$records)/*: void*/
    {
        $fks = $t2->getFksTo($t1->getName());
        $columnNames = $this->columns->getNames($t2, false, $params);
        $pkValueKeys = implode(',', array_keys($pkValues));
        $conditions = array();
        foreach ($fks as $fk) {
            $conditions[] = new ColumnCondition($fk, 'in', $pkValueKeys);
        }
        $condition = OrCondition::fromArray($conditions);
        foreach ($db->selectAllUnordered($t2, $columnNames, $condition) as $record) {
            $records[] = $record;
        }
    }

    private function fillPkValues(ReflectedTable $t1, ReflectedTable $t2, array $pkRecords, array &$pkValues)/*: void*/
    {
        $fks = $t2->getFksTo($t1->getName());
        foreach ($fks as $fk) {
            $fkName = $fk->getName();
            foreach ($pkRecords as $pkRecord) {
                $key = $pkRecord[$fkName];
                if (isset($pkValues[$key])) {
                    $pkValues[$key][] = $pkRecord;
                }
            }
        }
    }

    private function setPkValues(ReflectedTable $t1, ReflectedTable $t2, array &$records, array $pkValues)/*: void*/
    {
        $pkName = $t1->getPk()->getName();
        $t2Name = $t2->getName();

        foreach ($records as $i => $record) {
            $key = $record[$pkName];
            $records[$i][$t2Name] = $pkValues[$key];
        }
    }

    private function getHabtmEmptyValues(ReflectedTable $t1, ReflectedTable $t2, ReflectedTable $t3, GenericDB $db, array $records): HabtmValues
    {
        $pkValues = $this->getPkEmptyValues($t1, $records);
        $fkValues = array();

        $fk1 = $t3->getFksTo($t1->getName())[0];
        $fk2 = $t3->getFksTo($t2->getName())[0];

        $fk1Name = $fk1->getName();
        $fk2Name = $fk2->getName();

        $columnNames = array($fk1Name, $fk2Name);

        $pkIds = implode(',', array_keys($pkValues));
        $condition = new ColumnCondition($t3->get($fk1Name), 'in', $pkIds);

        $records = $db->selectAllUnordered($t3, $columnNames, $condition);
        foreach ($records as $record) {
            $val1 = $record[$fk1Name];
            $val2 = $record[$fk2Name];
            $pkValues[$val1][] = $val2;
            $fkValues[$val2] = null;
        }

        return new HabtmValues($pkValues, $fkValues);
    }

    private function setHabtmValues(ReflectedTable $t1, ReflectedTable $t3, array &$records, HabtmValues $habtmValues)/*: void*/
    {
        $pkName = $t1->getPk()->getName();
        $t3Name = $t3->getName();
        foreach ($records as $i => $record) {
            $key = $record[$pkName];
            $val = array();
            $fks = $habtmValues->pkValues[$key];
            foreach ($fks as $fk) {
                $val[] = $habtmValues->fkValues[$fk];
            }
            $records[$i][$t3Name] = $val;
        }
    }
}

// file: src/Tqd/PhpCrudApi/Database/ColumnConverter.php

class ColumnConverter
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    public function convertColumnValue(ReflectedColumn $column): String
    {
        if ($column->isBinary()) {
            switch ($this->driver) {
                case 'mysql':
                    return "FROM_BASE64(?)";
                case 'pgsql':
                    return "decode(?, 'base64')";
                case 'sqlsrv':
                    return "CONVERT(XML, ?).value('.','varbinary(max)')";
            }
        }
        if ($column->isGeometry()) {
            switch ($this->driver) {
                case 'mysql':
                case 'pgsql':
                    return "ST_GeomFromText(?)";
                case 'sqlsrv':
                    return "geometry::STGeomFromText(?,0)";
            }
        }
        return '?';
    }

    public function convertColumnName(ReflectedColumn $column, $value): String
    {
        if ($column->isBinary()) {
            switch ($this->driver) {
                case 'mysql':
                    return "TO_BASE64($value) as $value";
                case 'pgsql':
                    return "encode($value::bytea, 'base64') as $value";
                case 'sqlsrv':
                    return "CAST(N'' AS XML).value('xs:base64Binary(xs:hexBinary(sql:column($value)))', 'VARCHAR(MAX)') as $value";

            }
        }
        if ($column->isGeometry()) {
            switch ($this->driver) {
                case 'mysql':
                case 'pgsql':
                    return "ST_AsText($value) as $value";
                case 'sqlsrv':
                    return "REPLACE($value.STAsText(),' (','(') as $value";
            }
        }
        return $value;
    }

}

// file: src/Tqd/PhpCrudApi/Database/ColumnsBuilder.php

class ColumnsBuilder
{
    private $driver;
    private $converter;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
        $this->converter = new ColumnConverter($driver);
    }

    public function getOffsetLimit(int $offset, int $limit): String
    {
        if ($limit < 0 || $offset < 0) {
            return '';
        }
        switch ($this->driver) {
            case 'mysql':return "LIMIT $offset, $limit";
            case 'pgsql':return "LIMIT $limit OFFSET $offset";
            case 'sqlsrv':return "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        }
    }

    private function quoteColumnName(ReflectedColumn $column): String
    {
        return '"' . $column->getName() . '"';
    }

    public function getOrderBy(ReflectedTable $table, array $columnOrdering): String
    {
        $results = array();
        foreach ($columnOrdering as $i => list($columnName, $ordering)) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName . ' ' . $ordering;
        }
        return implode(',', $results);
    }

    public function getSelect(ReflectedTable $table, array $columnNames): String
    {
        $results = array();
        foreach ($columnNames as $columnName) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $quotedColumnName = $this->converter->convertColumnName($column, $quotedColumnName);
            $results[] = $quotedColumnName;
        }
        return implode(',', $results);
    }

    public function getInsert(ReflectedTable $table, array $columnValues): String
    {
        $columns = array();
        $values = array();
        foreach ($columnValues as $columnName => $columnValue) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $columns[] = $quotedColumnName;
            $columnValue = $this->converter->convertColumnValue($column);
            $values[] = $columnValue;
        }
        $columnsSql = '(' . implode(',', $columns) . ')';
        $valuesSql = '(' . implode(',', $values) . ')';
        $outputColumn = $this->quoteColumnName($table->getPk());
        switch ($this->driver) {
            case 'mysql':return "$columnsSql VALUES $valuesSql";
            case 'pgsql':return "$columnsSql VALUES $valuesSql RETURNING $outputColumn";
            case 'sqlsrv':return "$columnsSql OUTPUT INSERTED.$outputColumn VALUES $valuesSql";
        }
    }

    public function getUpdate(ReflectedTable $table, array $columnValues): String
    {
        $results = array();
        foreach ($columnValues as $columnName => $columnValue) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $columnValue = $this->converter->convertColumnValue($column);
            $results[] = $quotedColumnName . '=' . $columnValue;
        }
        return implode(',', $results);
    }

    public function getIncrement(ReflectedTable $table, array $columnValues): String
    {
        $results = array();
        foreach ($columnValues as $columnName => $columnValue) {
            if (!is_numeric($columnValue)) {
                continue;
            }
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $columnValue = $this->converter->convertColumnValue($column);
            $results[] = $quotedColumnName . '=' . $quotedColumnName . '+' . $columnValue;
        }
        return implode(',', $results);
    }

}

// file: src/Tqd/PhpCrudApi/Database/ConditionsBuilder.php

class ConditionsBuilder
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    private function getConditionSql(Condition $condition, array &$arguments): String
    {
        if ($condition instanceof AndCondition) {
            return $this->getAndConditionSql($condition, $arguments);
        }
        if ($condition instanceof OrCondition) {
            return $this->getOrConditionSql($condition, $arguments);
        }
        if ($condition instanceof NotCondition) {
            return $this->getNotConditionSql($condition, $arguments);
        }
        if ($condition instanceof ColumnCondition) {
            return $this->getColumnConditionSql($condition, $arguments);
        }
        if ($condition instanceof SpatialCondition) {
            return $this->getSpatialConditionSql($condition, $arguments);
        }
        throw new \Exception('Unknown Condition: ' . get_class($condition));
    }

    private function getAndConditionSql(AndCondition $and, array &$arguments): String
    {
        $parts = [];
        foreach ($and->getConditions() as $condition) {
            $parts[] = $this->getConditionSql($condition, $arguments);
        }
        return '(' . implode(' AND ', $parts) . ')';
    }

    private function getOrConditionSql(OrCondition $or, array &$arguments): String
    {
        $parts = [];
        foreach ($or->getConditions() as $condition) {
            $parts[] = $this->getConditionSql($condition, $arguments);
        }
        return '(' . implode(' OR ', $parts) . ')';
    }

    private function getNotConditionSql(NotCondition $not, array &$arguments): String
    {
        $condition = $not->getCondition();
        return '(NOT ' . $this->getConditionSql($condition, $arguments) . ')';
    }

    private function quoteColumnName(ReflectedColumn $column): String
    {
        return '"' . $column->getName() . '"';
    }

    private function escapeLikeValue(String $value): String
    {
        return addcslashes($value, '%_');
    }

    private function getColumnConditionSql(ColumnCondition $condition, array &$arguments): String
    {
        $column = $this->quoteColumnName($condition->getColumn());
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        switch ($operator) {
            case 'cs':
                $sql = "$column LIKE ?";
                $arguments[] = '%' . $this->escapeLikeValue($value) . '%';
                break;
            case 'sw':
                $sql = "$column LIKE ?";
                $arguments[] = $this->escapeLikeValue($value) . '%';
                break;
            case 'ew':
                $sql = "$column LIKE ?";
                $arguments[] = '%' . $this->escapeLikeValue($value);
                break;
            case 'eq':
                $sql = "$column = ?";
                $arguments[] = $value;
                break;
            case 'lt':
                $sql = "$column < ?";
                $arguments[] = $value;
                break;
            case 'le':
                $sql = "$column <= ?";
                $arguments[] = $value;
                break;
            case 'ge':
                $sql = "$column >= ?";
                $arguments[] = $value;
                break;
            case 'gt':
                $sql = "$column > ?";
                $arguments[] = $value;
                break;
            case 'bt':
                $parts = explode(',', $value, 2);
                $count = count($parts);
                if ($count == 2) {
                    $sql = "($column >= ? AND $column <= ?)";
                    $arguments[] = $parts[0];
                    $arguments[] = $parts[1];
                } else {
                    $sql = "FALSE";
                }
                break;
            case 'in':
                $parts = explode(',', $value);
                $count = count($parts);
                if ($count > 0) {
                    $qmarks = implode(',', str_split(str_repeat('?', $count)));
                    $sql = "$column IN ($qmarks)";
                    for ($i = 0; $i < $count; $i++) {
                        $arguments[] = $parts[$i];
                    }
                } else {
                    $sql = "FALSE";
                }
                break;
            case 'is':
                $sql = "$column IS NULL";
                break;
        }
        return $sql;
    }

    private function getSpatialFunctionName(String $operator): String
    {
        switch ($operator) {
            case 'co':return 'ST_Contains';
            case 'cr':return 'ST_Crosses';
            case 'di':return 'ST_Disjoint';
            case 'eq':return 'ST_Equals';
            case 'in':return 'ST_Intersects';
            case 'ov':return 'ST_Overlaps';
            case 'to':return 'ST_Touches';
            case 'wi':return 'ST_Within';
            case 'ic':return 'ST_IsClosed';
            case 'is':return 'ST_IsSimple';
            case 'iv':return 'ST_IsValid';
        }
    }

    private function hasSpatialArgument(String $operator): bool
    {
        return in_array($opertor, ['ic', 'is', 'iv']) ? false : true;
    }

    private function getSpatialFunctionCall(String $functionName, String $column, bool $hasArgument): String
    {
        switch ($this->driver) {
            case 'mysql':
            case 'pgsql':
                $argument = $hasArgument ? 'ST_GeomFromText(?)' : '';
                return "$functionName($column, $argument)=TRUE";
            case 'sql_srv':
                $functionName = str_replace('ST_', 'ST', $functionName);
                $argument = $hasArgument ? 'geometry::STGeomFromText(?,0)' : '';
                return "$column.$functionName($argument)=1";
        }
    }

    private function getSpatialConditionSql(ColumnCondition $condition, array &$arguments): String
    {
        $column = $this->quoteColumnName($condition->getColumn());
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        $functionName = $this->getSpatialFunctionName($operator);
        $hasArgument = $this->hasSpatialArgument($operator);
        $sql = $this->getSpatialFunctionCall($functionName, $column, $hasArgument);
        if ($hasArgument) {
            $arguments[] = $value;
        }
        return $sql;
    }

    public function getWhereClause(Condition $condition, array &$arguments): String
    {
        if ($condition instanceof NoCondition) {
            return '';
        }
        return ' WHERE ' . $this->getConditionSql($condition, $arguments);
    }
}

// file: src/Tqd/PhpCrudApi/Database/DataConverter.php

class DataConverter
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    private function convertRecordValue($conversion, $value)
    {
        switch ($conversion) {
            case 'boolean':
                return $value ? true : false;
        }
        return $value;
    }

    private function getRecordValueConversion(ReflectedColumn $column): String
    {
        if (in_array($this->driver, ['mysql', 'sqlsrv']) && $column->isBoolean()) {
            return 'boolean';
        }
        return 'none';
    }

    public function convertRecords(ReflectedTable $table, array $columnNames, array &$records) /*: void*/
    {
        foreach ($columnNames as $columnName) {
            $column = $table->get($columnName);
            $conversion = $this->getRecordValueConversion($column);
            if ($conversion != 'none') {
                foreach ($records as $i => $record) {
                    $value = $records[$i][$columnName];
                    if ($value === null) {
                        continue;
                    }
                    $records[$i][$columnName] = $this->convertRecordValue($conversion, $value);
                }
            }
        }
    }

    private function convertInputValue($conversion, $value)
    {
        switch ($conversion) {
            case 'base64url_to_base64':
                return str_pad(strtr($value, '-_', '+/'), ceil(strlen($value) / 4) * 4, '=', STR_PAD_RIGHT);
        }
        return $value;
    }

    private function getInputValueConversion(ReflectedColumn $column): String
    {
        if ($column->isBinary()) {
            return 'base64url_to_base64';
        }
        return 'none';
    }

    public function convertColumnValues(ReflectedTable $table, array &$columnValues) /*: void*/
    {
        $columnNames = array_keys($columnValues);
        foreach ($columnNames as $columnName) {
            $column = $table->get($columnName);
            $conversion = $this->getInputValueConversion($column);
            if ($conversion != 'none') {
                $value = $columnValues[$columnName];
                if ($value !== null) {
                    $columnValues[$columnName] = $this->convertInputValue($conversion, $value);
                }
            }
        }
    }
}

// file: src/Tqd/PhpCrudApi/Database/GenericDB.php

class GenericDB
{
    private $driver;
    private $database;
    private $pdo;
    private $meta;
    private $columns;
    private $conditions;
    private $converter;

    private function getDsn(String $address, String $port = null, String $database = null): String
    {
        switch ($this->driver) {
            case 'mysql':return "$this->driver:host=$address;port=$port;dbname=$database;charset=utf8mb4";
            case 'pgsql':return "$this->driver:host=$address port=$port dbname=$database options='--client_encoding=UTF8'";
            case 'sqlsrv':return "$this->driver:Server=$address,$port;Database=$database";
        }
    }

    private function getCommands(): array
    {
        switch ($this->driver) {
            case 'mysql':return [
                    'SET SESSION sql_warnings=1;',
                    'SET NAMES utf8mb4;',
                    'SET SESSION sql_mode = "ANSI,TRADITIONAL";',
                ];
            case 'pgsql':return [
                    "SET NAMES 'UTF8';",
                ];
            case 'sqlsrv':return [
                ];
        }
    }

    private function getOptions(): array
    {
        $options = array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        );
        switch ($this->driver) {
            case 'mysql':return $options + [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::MYSQL_ATTR_FOUND_ROWS => true,
                ];
            case 'pgsql':return $options + [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ];
            case 'sqlsrv':return $options + [
                    \PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true,
                ];
        }
    }

    public function __construct(String $driver, String $address, String $port = null, String $database = null, String $username = null, String $password = null)
    {
        $this->driver = $driver;
        $this->database = $database;
        $dsn = $this->getDsn($address, $port, $database);
        $options = $this->getOptions();
        $this->pdo = new \PDO($dsn, $username, $password, $options);
        $commands = $this->getCommands();
        foreach ($commands as $command) {
            $this->pdo->query($command);
        }
        $this->meta = new GenericMeta($this->pdo, $driver, $database);
        $this->conditions = new ConditionsBuilder($driver);
        $this->columns = new ColumnsBuilder($driver);
        $this->converter = new DataConverter($driver);
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function meta(): GenericMeta
    {
        return $this->meta;
    }

    public function createSingle(ReflectedTable $table, array $columnValues) /*: ?String*/
    {
        $this->converter->convertColumnValues($table, $columnValues);
        $insertColumns = $this->columns->getInsert($table, $columnValues);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName();
        $parameters = array_values($columnValues);
        $sql = 'INSERT INTO "' . $tableName . '" ' . $insertColumns;
        $stmt = $this->query($sql, $parameters);
        if (isset($columnValues[$pkName])) {
            return $columnValues[$pkName];
        }
        switch ($this->driver) {
            case 'mysql':
                $stmt = $this->query('SELECT LAST_INSERT_ID()', []);
                break;
        }
        return $stmt->fetchColumn(0);
    }

    public function selectSingle(ReflectedTable $table, array $columnNames, String $id) /*: ?array*/
    {
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $condition = new ColumnCondition($table->getPk(), 'eq', $id);
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $sql = 'SELECT ' . $selectColumns . ' FROM "' . $tableName . '" ' . $whereClause;
        $stmt = $this->query($sql, $parameters);
        $record = $stmt->fetch() ?: null;
        if ($record === null) {
            return null;
        }
        $records = array($record);
        $this->converter->convertRecords($table, $columnNames, $records);
        return $records[0];
    }

    public function selectMultiple(ReflectedTable $table, array $columnNames, array $ids): array
    {
        if (count($ids) == 0) {
            return [];
        }
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $condition = new ColumnCondition($table->getPk(), 'in', implode(',', $ids));
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $sql = 'SELECT ' . $selectColumns . ' FROM "' . $tableName . '" ' . $whereClause;
        $stmt = $this->query($sql, $parameters);
        $records = $stmt->fetchAll();
        $this->converter->convertRecords($table, $columnNames, $records);
        return $records;
    }

    public function selectCount(ReflectedTable $table, Condition $condition): int
    {
        $tableName = $table->getName();
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $sql = 'SELECT COUNT(*) FROM "' . $tableName . '"' . $whereClause;
        $stmt = $this->query($sql, $parameters);
        return $stmt->fetchColumn(0);
    }

    public function selectAllUnordered(ReflectedTable $table, array $columnNames, Condition $condition): array
    {
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $sql = 'SELECT ' . $selectColumns . ' FROM "' . $tableName . '"' . $whereClause;
        $stmt = $this->query($sql, $parameters);
        $records = $stmt->fetchAll();
        $this->converter->convertRecords($table, $columnNames, $records);
        return $records;
    }

    public function selectAll(ReflectedTable $table, array $columnNames, Condition $condition, array $columnOrdering, int $offset, int $limit): array
    {
        if ($limit == 0) {
            return array();
        }
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $orderBy = $this->columns->getOrderBy($table, $columnOrdering);
        $offsetLimit = $this->columns->getOffsetLimit($offset, $limit);
        $sql = 'SELECT ' . $selectColumns . ' FROM "' . $tableName . '"' . $whereClause . ' ORDER BY ' . $orderBy . ' ' . $offsetLimit;
        $stmt = $this->query($sql, $parameters);
        $records = $stmt->fetchAll();
        $this->converter->convertRecords($table, $columnNames, $records);
        return $records;
    }

    public function updateSingle(ReflectedTable $table, array $columnValues, String $id)
    {
        if (count($columnValues) == 0) {
            return 0;
        }
        $this->converter->convertColumnValues($table, $columnValues);
        $updateColumns = $this->columns->getUpdate($table, $columnValues);
        $tableName = $table->getName();
        $condition = new ColumnCondition($table->getPk(), 'eq', $id);
        $parameters = array_values($columnValues);
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $sql = 'UPDATE "' . $tableName . '" SET ' . $updateColumns . $whereClause;
        $stmt = $this->query($sql, $parameters);
        return $stmt->rowCount();
    }

    public function deleteSingle(ReflectedTable $table, String $id)
    {
        $tableName = $table->getName();
        $condition = new ColumnCondition($table->getPk(), 'eq', $id);
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($condition, $parameters);
        $sql = 'DELETE FROM "' . $tableName . '" ' . $whereClause;
        $stmt = $this->query($sql, $parameters);
        return $stmt->rowCount();
    }

    private function query(String $sql, array $parameters): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        return $stmt;
    }
}

// file: src/Tqd/PhpCrudApi/Database/GenericMeta.php

class GenericMeta
{
    private $pdo;
    private $driver;
    private $database;
    private $typeConverter;

    public function __construct(\PDO $pdo, String $driver, String $database)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->database = $database;
        $this->typeConverter = new TypeConverter($driver);
    }

    public function getIgnoredTables(): array
    {
        switch ($this->driver) {
            case 'mysql':return [];
            case 'pgsql':return ['spatial_ref_sys'];
            case 'sqlsrv':return [];
        }
    }

    private function getTablesSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "TABLE_NAME" FROM "INFORMATION_SCHEMA"."TABLES" WHERE "TABLE_TYPE" IN (\'BASE TABLE\') AND "TABLE_SCHEMA" = ? ORDER BY BINARY "TABLE_NAME"';
            case 'pgsql':return 'SELECT c.relname as "TABLE_NAME" FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE c.relkind IN (\'r\') AND n.nspname <> \'pg_catalog\' AND n.nspname <> \'information_schema\' AND n.nspname !~ \'^pg_toast\' AND pg_catalog.pg_table_is_visible(c.oid) AND \'\' <> ? ORDER BY "TABLE_NAME";';
            case 'sqlsrv':return 'SELECT o.name as "TABLE_NAME" FROM sysobjects o WHERE o.xtype = \'U\' ORDER BY "TABLE_NAME"';
        }
    }

    private function getTableColumnsSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "COLUMN_NAME", "IS_NULLABLE", "DATA_TYPE", "CHARACTER_MAXIMUM_LENGTH", "NUMERIC_PRECISION", "NUMERIC_SCALE" FROM "INFORMATION_SCHEMA"."COLUMNS" WHERE "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT a.attname AS "COLUMN_NAME", case when a.attnotnull then \'NO\' else \'YES\' end as "IS_NULLABLE", pg_catalog.format_type(a.atttypid, -1) as "DATA_TYPE", case when a.atttypmod < 0 then NULL else a.atttypmod-4 end as "CHARACTER_MAXIMUM_LENGTH", case when a.atttypid != 1700 then NULL else ((a.atttypmod - 4) >> 16) & 65535 end as "NUMERIC_PRECISION", case when a.atttypid != 1700 then NULL else (a.atttypmod - 4) & 65535 end as "NUMERIC_SCALE" FROM pg_attribute a JOIN pg_class pgc ON pgc.oid = a.attrelid WHERE pgc.relname = ? AND \'\' <> ? AND a.attnum > 0 AND NOT a.attisdropped;';
            case 'sqlsrv':return 'SELECT c.name AS "COLUMN_NAME", c.is_nullable AS "IS_NULLABLE", t.Name AS "DATA_TYPE", (c.max_length/2) AS "CHARACTER_MAXIMUM_LENGTH", c.precision AS "NUMERIC_PRECISION", c.scale AS "NUMERIC_SCALE" FROM sys.columns c INNER JOIN sys.types t ON c.user_type_id = t.user_type_id WHERE c.object_id = OBJECT_ID(?) AND \'\' <> ?';
        }
    }

    private function getTablePrimaryKeysSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "COLUMN_NAME" FROM "INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" WHERE "CONSTRAINT_NAME" = \'PRIMARY\' AND "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT a.attname AS "COLUMN_NAME" FROM pg_attribute a JOIN pg_constraint c ON (c.conrelid, c.conkey[1]) = (a.attrelid, a.attnum) JOIN pg_class pgc ON pgc.oid = a.attrelid WHERE pgc.relname = ? AND \'\' <> ? AND c.contype = \'p\'';
            case 'sqlsrv':return 'SELECT c.NAME as "COLUMN_NAME" FROM sys.key_constraints kc inner join sys.objects t on t.object_id = kc.parent_object_id INNER JOIN sys.index_columns ic ON kc.parent_object_id = ic.object_id and kc.unique_index_id = ic.index_id INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id WHERE kc.type = \'PK\' and t.object_id = OBJECT_ID(?) and \'\' <> ?';
        }
    }

    private function getTableForeignKeysSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "COLUMN_NAME", "REFERENCED_TABLE_NAME" FROM "INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" WHERE "REFERENCED_TABLE_NAME" IS NOT NULL AND "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT a.attname AS "COLUMN_NAME", c.confrelid::regclass::text AS "REFERENCED_TABLE_NAME" FROM pg_attribute a JOIN pg_constraint c ON (c.conrelid, c.conkey[1]) = (a.attrelid, a.attnum) JOIN pg_class pgc ON pgc.oid = a.attrelid WHERE pgc.relname = ? AND \'\' <> ? AND c.contype  = \'f\'';
            case 'sqlsrv':return 'SELECT COL_NAME(fc.parent_object_id, fc.parent_column_id) AS "COLUMN_NAME", OBJECT_NAME (f.referenced_object_id) AS "REFERENCED_TABLE_NAME" FROM sys.foreign_keys AS f INNER JOIN sys.foreign_key_columns AS fc ON f.OBJECT_ID = fc.constraint_object_id WHERE f.parent_object_id = OBJECT_ID(?) and \'\' <> ?';
        }
    }

    public function getDatabaseName(): String
    {
        return $this->database;
    }

    public function getTables(): array
    {
        $stmt = $this->pdo->prepare($this->getTablesSQL());
        $stmt->execute([$this->database]);
        return $stmt->fetchAll();
    }

    public function getTableColumns(String $tableName): array
    {
        $stmt = $this->pdo->prepare($this->getTableColumnsSQL());
        $stmt->execute([$tableName, $this->database]);
        return $stmt->fetchAll();
    }

    public function getTablePrimaryKeys(String $tableName): array
    {
        $stmt = $this->pdo->prepare($this->getTablePrimaryKeysSQL());
        $stmt->execute([$tableName, $this->database]);
        $results = $stmt->fetchAll();
        $primaryKeys = [];
        foreach ($results as $result) {
            $primaryKeys[] = $result['COLUMN_NAME'];
        }
        return $primaryKeys;
    }

    public function getTableForeignKeys(String $tableName): array
    {
        $stmt = $this->pdo->prepare($this->getTableForeignKeysSQL());
        $stmt->execute([$tableName, $this->database]);
        $results = $stmt->fetchAll();
        $foreignKeys = [];
        foreach ($results as $result) {
            $foreignKeys[$result['COLUMN_NAME']] = $result['REFERENCED_TABLE_NAME'];
        }
        return $foreignKeys;
    }

    public function getTypeConverter(): TypeConverter
    {
        return $this->typeConverter;
    }
}

// file: src/Tqd/PhpCrudApi/Database/TypeConverter.php

class TypeConverter
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    private $fromSimplifiedJdbc = [
        'mysql' => [
            'clob' => 'longtext',
            'boolean' => 'bit',
            'blob' => 'longblob',
            'timestamp' => 'datetime',
        ],
        'sqlsrv' => [
            'boolean' => 'bit',
        ],
    ];

    private $toJdbc = [
        'simplified' => [
            'char' => 'varchar',
            'longvarchar' => 'clob',
            'nchar' => 'varchar',
            'nvarchar' => 'varchar',
            'longnvarchar' => 'clob',
            'binary' => 'varbinary',
            'longvarbinary' => 'blob',
            'tinyint' => 'integer',
            'smallint' => 'integer',
            'real' => 'float',
            'numeric' => 'decimal',
            'time_with_timezone' => 'time',
            'timestamp_with_timezone' => 'timestamp',
        ],
        'mysql' => [
            'tinyint(1)' => 'boolean',
            'bit(0)' => 'boolean',
            'bit(1)' => 'boolean',
            'tinyblob' => 'blob',
            'mediumblob' => 'blob',
            'longblob' => 'blob',
            'tinytext' => 'clob',
            'mediumtext' => 'clob',
            'longtext' => 'clob',
            'text' => 'clob',
            'int' => 'integer',
            'polygon' => 'geometry',
            'point' => 'geometry',
            'datetime' => 'timestamp',
        ],
        'pgsql' => [
            'bigserial' => 'bigint',
            'bit varying' => 'bit',
            'box' => 'geometry',
            'bytea' => 'blob',
            'character varying' => 'varchar',
            'character' => 'char',
            'cidr' => 'varchar',
            'circle' => 'geometry',
            'double precision' => 'double',
            'inet' => 'integer',
            'jsonb' => 'clob',
            'line' => 'geometry',
            'lseg' => 'geometry',
            'macaddr' => 'varchar',
            'money' => 'decimal',
            'path' => 'geometry',
            'point' => 'geometry',
            'polygon' => 'geometry',
            'real' => 'float',
            'serial' => 'integer',
            'text' => 'clob',
            'time without time zone' => 'time',
            'time with time zone' => 'time_with_timezone',
            'timestamp without time zone' => 'timestamp',
            'timestamp with time zone' => 'timestamp_with_timezone',
            'uuid' => 'char',
            'xml' => 'clob',
        ],
        'sqlsrv' => [
            'varbinary(0)' => 'blob',
            'bit' => 'boolean',
            'datetime' => 'timestamp',
            'datetime2' => 'timestamp',
            'float' => 'double',
            'image' => 'longvarbinary',
            'int' => 'integer',
            'money' => 'decimal',
            'ntext' => 'longnvarchar',
            'smalldatetime' => 'timestamp',
            'smallmoney' => 'decimal',
            'text' => 'longvarchar',
            'timestamp' => 'binary',
            'tinyint' => 'tinyint',
            'udt' => 'varbinary',
            'uniqueidentifier' => 'char',
            'xml' => 'longnvarchar',
        ],
    ];

    private $valid = [
        'bigint' => true,
        'binary' => true,
        'bit' => true,
        'blob' => true,
        'boolean' => true,
        'char' => true,
        'clob' => true,
        'date' => true,
        'decimal' => true,
        'distinct' => true,
        'double' => true,
        'float' => true,
        'integer' => true,
        'longnvarchar' => true,
        'longvarbinary' => true,
        'longvarchar' => true,
        'nchar' => true,
        'nclob' => true,
        'numeric' => true,
        'nvarchar' => true,
        'real' => true,
        'smallint' => true,
        'time' => true,
        'time_with_timezone' => true,
        'timestamp' => true,
        'timestamp_with_timezone' => true,
        'tinyint' => true,
        'varbinary' => true,
        'varchar' => true,
        'geometry' => true,
    ];

    public function toJdbc(String $type, int $size): String
    {
        $jdbcType = strtolower($type);
        if (isset($this->toJdbc[$this->driver]["$jdbcType($size)"])) {
            $jdbcType = $this->toJdbc[$this->driver]["$jdbcType($size)"];
        }
        if (isset($this->toJdbc[$this->driver][$jdbcType])) {
            $jdbcType = $this->toJdbc[$this->driver][$jdbcType];
        }
        if (isset($this->toJdbc['simplified'][$jdbcType])) {
            $jdbcType = $this->toJdbc['simplified'][$jdbcType];
        }
        if (!isset($this->valid[$jdbcType])) {
            throw new \Exception("Unsupported type '$jdbcType' for driver '$this->driver'");
        }
        return $jdbcType;
    }
}

// file: src/Tqd/PhpCrudApi/Meta/Reflection/ReflectedColumn.php

class ReflectedColumn implements \JsonSerializable
{
    const DEFAULT_LENGTH = 255;
    const DEFAULT_PRECISION = 19;
    const DEFAULT_SCALE = 4;

    private $name;
    private $type;
    private $length;
    private $precision;
    private $scale;
    private $nullable;
    private $pk;
    private $fk;

    public function __construct(String $name, String $type, int $length, int $precision, int $scale, bool $nullable, bool $pk, String $fk)
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->nullable = $nullable;
        $this->pk = $pk;
        $this->fk = $fk;
        $this->sanitize();
    }

    public static function fromMeta(GenericMeta $meta, array $columnResult): ReflectedColumn
    {
        $name = $columnResult['COLUMN_NAME'];
        $length = $columnResult['CHARACTER_MAXIMUM_LENGTH'] + 0;
        $type = $meta->getTypeConverter()->toJdbc($columnResult['DATA_TYPE'], $length);
        $precision = $columnResult['NUMERIC_PRECISION'] + 0;
        $scale = $columnResult['NUMERIC_SCALE'] + 0;
        $nullable = in_array(strtoupper($columnResult['IS_NULLABLE']), ['TRUE', 'YES', 'T', 'Y', '1']);
        $pk = false;
        $fk = '';
        return new ReflectedColumn($name, $type, $length, $precision, $scale, $nullable, $pk, $fk);
    }

    public static function fromJson( /* object */$json): ReflectedColumn
    {
        $name = $json->name;
        $type = $json->type;
        $length = isset($json->length) ? $json->length : 0;
        $precision = isset($json->precision) ? $json->precision : 0;
        $scale = isset($json->scale) ? $json->scale : 0;
        $nullable = isset($json->nullable) ? $json->nullable : false;
        $pk = isset($json->pk) ? $json->pk : false;
        $fk = isset($json->fk) ? $json->fk : '';
        return new ReflectedColumn($name, $type, $length, $precision, $scale, $nullable, $pk, $fk);
    }

    private function sanitize()
    {
        $this->length = $this->hasLength() ? $this->getLength() : 0;
        $this->precision = $this->hasPrecision() ? $this->getPrecision() : 0;
        $this->scale = $this->hasScale() ? $this->getScale() : 0;
    }

    public function getName(): String
    {
        return $this->name;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getType(): String
    {
        return $this->type;
    }

    public function getLength(): int
    {
        return $this->length ?: self::DEFAULT_LENGTH;
    }

    public function getPrecision(): int
    {
        return $this->precision ?: self::DEFAULT_PRECISION;
    }

    public function getScale(): int
    {
        return $this->scale ?: self::DEFAULT_SCALE;
    }

    public function hasLength(): bool
    {
        return in_array($this->type, ['varchar', 'varbinary']);
    }

    public function hasPrecision(): bool
    {
        return $this->type == 'decimal';
    }

    public function hasScale(): bool
    {
        return $this->type == 'decimal';
    }

    public function isBinary(): bool
    {
        return in_array($this->type, ['blob', 'varbinary']);
    }

    public function isBoolean(): bool
    {
        return $this->type == 'boolean';
    }

    public function isGeometry(): bool
    {
        return $this->type == 'geometry';
    }

    public function setPk($value) /*: void*/
    {
        $this->pk = $value;
    }

    public function getPk(): bool
    {
        return $this->pk;
    }

    public function setFk($value) /*: void*/
    {
        $this->fk = $value;
    }

    public function getFk(): String
    {
        return $this->fk;
    }

    public function jsonSerialize()
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'nullable' => $this->nullable,
            'pk' => $this->pk,
            'fk' => $this->fk,
        ]);
    }
}

// file: src/Tqd/PhpCrudApi/Meta/Reflection/ReflectedDatabase.php

class ReflectedDatabase implements \JsonSerializable
{
    private $name;
    private $tables;

    public function __construct(String $name, array $tables)
    {
        $this->name = $name;
        $this->tables = [];
        foreach ($tables as $table) {
            $this->tables[$table->getName()] = $table;
        }
    }

    public static function fromMeta(GenericMeta $meta): ReflectedDatabase
    {
        $name = $meta->getDatabaseName();
        $tables = [];
        foreach ($meta->getTables() as $tableName) {
            if (in_array($tableName['TABLE_NAME'], $meta->getIgnoredTables())) {
                continue;
            }
            $table = ReflectedTable::fromMeta($meta, $tableName);
            $tables[$table->getName()] = $table;
        }
        return new ReflectedDatabase($name, array_values($tables));
    }

    public static function fromJson(/* object */$json): ReflectedDatabase
    {
        $name = $json->name;
        $tables = [];
        if (isset($json->tables) && is_array($json->tables)) {
            foreach ($json->tables as $table) {
                $tables[] = ReflectedTable::fromJson($table);
            }
        }
        return new ReflectedDatabase($name, $tables);
    }

    public function exists(String $tableName): bool
    {
        return isset($this->tables[$tableName]);
    }

    public function get(String $tableName): ReflectedTable
    {
        return $this->tables[$tableName];
    }

    public function getTableNames(): array
    {
        return array_keys($this->tables);
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'tables' => array_values($this->tables),
        ];
    }
}

// file: src/Tqd/PhpCrudApi/Meta/Reflection/ReflectedTable.php

class ReflectedTable implements \JsonSerializable
{
    private $name;
    private $columns;
    private $pk;
    private $fks;

    public function __construct(String $name, array $columns)
    {
        $this->name = $name;
        $this->columns = [];
        foreach ($columns as $column) {
            $columnName = $column->getName();
            $this->columns[$columnName] = $column;
        }
        $this->pk = null;
        foreach ($columns as $column) {
            if ($column->getPk() == true) {
                $this->pk = $column;
            }
        }
        $this->fks = [];
        foreach ($columns as $column) {
            $columnName = $column->getName();
            $referencedTableName = $column->getFk();
            if ($referencedTableName != '') {
                $this->fks[$columnName] = $referencedTableName;
            }
        }
    }

    public static function fromMeta(GenericMeta $meta, array $tableResult): ReflectedTable
    {
        $name = $tableResult['TABLE_NAME'];
        $columns = [];
        foreach ($meta->getTableColumns($name) as $tableColumn) {
            $column = ReflectedColumn::fromMeta($meta, $tableColumn);
            $columns[$column->getName()] = $column;
        }
        $columnNames = $meta->getTablePrimaryKeys($name);
        if (count($columnNames) == 1) {
            $columnName = $columnNames[0];
            if (isset($columns[$columnName])) {
                $pk = $columns[$columnName];
                $pk->setPk(true);
            }
        }
        $fks = $meta->getTableForeignKeys($name);
        foreach ($fks as $columnName => $table) {
            $columns[$columnName]->setFk($table);
        }
        return new ReflectedTable($name, array_values($columns));
    }

    public static function fromJson(/* object */$json): ReflectedTable
    {
        $name = $json->name;
        $columns = [];
        if (isset($json->columns) && is_array($json->columns)) {
            foreach ($json->columns as $column) {
                $columns[] = ReflectedColumn::fromJson($column);
            }
        }
        return new ReflectedTable($name, $columns);
    }

    public function exists(String $columnName): bool
    {
        return isset($this->columns[$columnName]);
    }

    public function getPk(): ReflectedColumn
    {
        return $this->pk;
    }

    public function getName(): String
    {
        return $this->name;
    }

    public function columnNames(): array
    {
        return array_keys($this->columns);
    }

    public function get($columnName): ReflectedColumn
    {
        return $this->columns[$columnName];
    }

    public function getFksTo(String $tableName): array
    {
        $columns = array();
        foreach ($this->fks as $columnName => $referencedTableName) {
            if ($tableName == $referencedTableName) {
                $columns[] = $this->columns[$columnName];
            }
        }
        return $columns;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'columns' => array_values($this->columns),
        ];
    }
}

// file: src/Tqd/PhpCrudApi/Meta/MetaService.php

class MetaService
{
    private $db;
    private $cache;
    private $tables;

    public function __construct(GenericDB $db, Cache $cache, int $ttl)
    {
        $this->db = $db;
        $this->cache = $cache;
        $data = $this->cache->get('ReflectedDatabase');
        if ($data != '') {
            $this->tables = ReflectedDatabase::fromJson(json_decode(gzuncompress($data)));
        } else {
            $this->tables = ReflectedDatabase::fromMeta($db->meta());
            $data = gzcompress(json_encode($this->tables, JSON_UNESCAPED_UNICODE));
            $this->cache->set('ReflectedDatabase', $data, $ttl);
        }
    }

    public function hasTable(String $table): bool
    {
        return $this->tables->exists($table);
    }

    public function getTable(String $table): ReflectedTable
    {
        return $this->tables->get($table);
    }

    public function getDatabase(): ReflectedDatabase
    {
        return $this->tables;
    }
}

// file: src/Tqd/PhpCrudApi/OpenApi/DefaultOpenApiDefinition.php

class DefaultOpenApiDefinition
{
    private $root = [
        "openapi" => "3.0.0",
        "info" => [
            "title" => "JAVA-CRUD-API",
            "version" => "1.0.0",
        ],
        "paths" => [],
        "components" => [
            "schemas" => [
                "Category" => [
                    "type" => "object",
                    "properties" => [
                        "id" => [
                            "type" => "integer",
                            "format" => "int64",
                        ],
                        "name" => [
                            "type" => "string",
                        ],
                    ],
                ],
                "Tag" => [
                    "type" => "object",
                    "properties" => [
                        "id" => [
                            "type" => "integer",
                            "format" => "int64",
                        ],
                        "name" => [
                            "type" => "string",
                        ],
                    ],
                ],
            ],
        ],
    ];
}

// file: src/Tqd/PhpCrudApi/OpenApi/OpenApiDefinition.php

class OpenApiDefinition extends DefaultOpenApiDefinition
{
    private function set(String $path, String $value) /*: void*/
    {
        $parts = explode('/', trim($path, '/'));
        $current = &$this->root;
        while (count($parts) > 0) {
            $part = array_shift($parts);
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
        $current = $value;
    }

    public function setPaths(DatabaseDefinition $database): void
    {
        $result = [];
        foreach ($database->getTables() as $database) {
            $path = sprintf('/data/%s', $table->getName());
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                $this->set("/paths/$path/$method/description", "$method operation");
            }
        }
    }

    private function fillParametersWithPrimaryKey(String $method, TableDefinition $table) /*: void*/
    {
        if (table . getPk() != null) {
            $pathWithId = sprintf('/data/%s/{%s}', $table->getName(), $table->getPk()->getName());
            $this->set("/paths/$pathWithId/$method/responses/200/description", "$method operation");
        }
    }
}

// file: src/Tqd/PhpCrudApi/OpenApi/OpenApiService.php

class OpenApiService
{
    private $tables;

    public function __construct(MetaService $meta)
    {
        $this->tables = $meta->getDatabase();
    }

}

// file: src/Tqd/PhpCrudApi/Router/Handler.php

interface Handler
{
    public function handle(Request $request): Response;
}

// file: src/Tqd/PhpCrudApi/Router/Middleware.php

abstract class Middleware implements Handler
{
    protected $next;

    public function setNext(Handler $handler) /*: void*/
    {
        $this->next = $handler;
    }
}

// file: src/Tqd/PhpCrudApi/Router/Router.php

interface Router extends Handler
{
    public function register(String $method, String $path, array $handler);

    public function load(Middleware $middleware);

    public function route(Request $request): Response;
}

// file: src/Tqd/PhpCrudApi/Router/SecurityHeaders.php

class SecurityHeaders extends Middleware
{
    private $allowedOrigins;

    public function __construct(Router $router, Responder $responder, String $allowedOrigins)
    {
        $router->load($this);
        $this->allowedOrigins = $allowedOrigins;
    }

    private function isOriginAllowed(String $origin, String $allowedOrigins): bool
    {
        $found = false;
        foreach (explode(',', $allowedOrigins) as $allowedOrigin) {
            $hostname = preg_quote(strtolower(trim($allowedOrigin)));
            $regex = '/^' . str_replace('\*', '.*', $hostname) . '$/';
            if (preg_match($regex, $origin)) {
                $found = true;
                break;
            }
        }
        return $found;
    }

    public function handle(Request $request): Response
    {
        $origin = $request->getHeader('Origin');
        if ($origin) {
            $allowedOrigins = $this->allowedOrigins;
            if (!$this->isOriginAllowed($origin, $allowedOrigins)) {
                return $this->responder->error(ErrorCode::ORIGIN_FORBIDDEN, $origin);
            }
        }
        $method = $request->getMethod();
        if ($method == 'OPTIONS') {
            $response = new Response(Response::OK, '');
            $response->addHeader('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN');
            $response->addHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, PUT, POST, DELETE, PATCH');
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
            $response->addHeader('Access-Control-Max-Age', '1728000');
        } else {
            $response = $this->next->handle($request);
        }
        if ($origin) {
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
            $response->addHeader('Access-Control-Allow-Origin', $origin);
        }
        return $response;
    }
}

// file: src/Tqd/PhpCrudApi/Router/SimpleRouter.php

class SimpleRouter implements Router
{
    private $responder;
    private $routes;
    private $midlewares;

    public function __construct(Responder $responder)
    {
        $this->responder = $responder;
        $this->routes = new PathTree();
        $this->middlewares = array();
    }

    public function register(String $method, String $path, array $handler)
    {
        $parts = explode('/', trim($path, '/'));
        array_unshift($parts, $method);
        $this->routes->put($parts, $handler);
    }

    public function load(Middleware $middleware)/*: void*/
    {
        if (count($this->middlewares) > 0) {
            $next = $this->middlewares[0];
        } else {
            $next = $this;
        }
        $middleware->setNext($next);
        array_unshift($this->middlewares, $middleware);
    }

    public function route(Request $request): Response
    {
        $obj = $this;
        if (count($this->middlewares) > 0) {
            $obj = $this->middlewares[0];
        }
        return $obj->handle($request);
    }

    public function handle(Request $request): Response
    {
        $method = strtoupper($request->getMethod());
        $path = explode('/', trim($request->getPath(0), '/'));
        array_unshift($path, $method);

        $functions = $this->matchPath($path, $this->routes);
        if (count($functions) == 0) {
            return $this->responder->error(ErrorCode::ROUTE_NOT_FOUND, $request->getPath());
        }
        return call_user_func($functions[0], $request);
    }

    private function matchPath(array $path, PathTree $tree): array
    {
        $values = array();
        while (count($path) > 0) {
            $key = array_shift($path);
            if ($tree->has($key)) {
                $tree = $tree->get($key);
            } else if ($tree->has('*')) {
                $tree = $tree->get('*');
            } else {
                $tree = null;
                break;
            }
        }
        if ($tree !== null) {
            $values = $tree->getValues();
        }
        return $values;
    }
}

// file: src/Tqd/PhpCrudApi/Api.php

class Api
{
    private $router;
    private $responder;
    private $debug;

    public function __construct(Config $config)
    {
        $db = new GenericDB(
            $config->getDriver(),
            $config->getAddress(),
            $config->getPort(),
            $config->getDatabase(),
            $config->getUsername(),
            $config->getPassword()
        );
        $cache = CacheFactory::create($config);
        $meta = new MetaService($db, $cache, $config->getCacheTime());
        $responder = new Responder();
        $router = new SimpleRouter($responder);
        new SecurityHeaders($router, $responder, $config->getAllowedOrigins());
        $data = new DataService($db, $meta);
        $openApi = new OpenApiService($meta);
        new DataController($router, $responder, $data);
        new MetaController($router, $responder, $meta);
        new CacheController($router, $responder, $cache);
        new OpenApiController($router, $responder, $openApi);
        $this->router = $router;
        $this->responder = $responder;
        $this->debug = $config->getDebug();
    }

    public function handle(Request $request): Response
    {
        $response = null;
        try {
            $response = $this->router->route($request);
        } catch (\Throwable $e) {
            if ($e instanceof \PDOException) {
                if (strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
                    return $this->responder->error(ErrorCode::DUPLICATE_KEY_EXCEPTION, '');
                }
                if (strpos(strtolower($e->getMessage()), 'default value') !== false) {
                    return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
                }
                if (strpos(strtolower($e->getMessage()), 'allow nulls') !== false) {
                    return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
                }
                if (strpos(strtolower($e->getMessage()), 'constraint') !== false) {
                    return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
                }
            }
            $response = $this->responder->error(ErrorCode::ERROR_NOT_FOUND, $e->getMessage());
            if ($this->debug) {
                $response->addHeader('X-Debug-Info', 'Exception in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        }
        return $response;
    }
}

// file: src/Tqd/PhpCrudApi/Config.php

class Config
{
    private $values = [
        'driver' => null,
        'address' => 'localhost',
        'port' => null,
        'username' => null,
        'password' => null,
        'database' => null,
        'allowedOrigins' => '*',
        'cacheType' => 'TempFile',
        'cachePath' => '',
        'cacheTime' => 10,
        'debug' => false,
    ];

    private function getDefaultDriver(array $values): String
    {
        if (isset($values['driver'])) {
            return $values['driver'];
        }
        return 'mysql';
    }

    private function getDefaultPort(String $driver): int
    {
        switch ($driver) {
            case 'mysql':return 3306;
            case 'pgsql':return 5432;
            case 'sqlsrv':return 1433;
        }
    }

    private function getDefaultAddress(String $driver): String
    {
        switch ($driver) {
            case 'mysql':return 'localhost';
            case 'pgsql':return 'localhost';
            case 'sqlsrv':return 'localhost';
        }
    }

    private function getDriverDefaults(String $driver): array
    {
        return [
            'driver' => $driver,
            'address' => $this->getDefaultAddress($driver),
            'port' => $this->getDefaultPort($driver),
        ];
    }

    public function __construct(array $values)
    {
        $driver = $this->getDefaultDriver($values);
        $defaults = $this->getDriverDefaults($driver);
        $newValues = array_merge($this->values, $defaults, $values);
        $diff = array_diff_key($newValues, $this->values);
        if (!empty($diff)) {
            $key = array_keys($diff)[0];
            throw new \Exception("Config has invalid value '$key'");
        }
        $this->values = $newValues;
    }

    public function getDriver(): String
    {
        return $this->values['driver'];
    }

    public function getAddress(): String
    {
        return $this->values['address'];
    }

    public function getPort(): int
    {
        return $this->values['port'];
    }

    public function getUsername(): String
    {
        return $this->values['username'];
    }

    public function getPassword(): String
    {
        return $this->values['password'];
    }

    public function getDatabase(): String
    {
        return $this->values['database'];
    }

    public function getAllowedOrigins(): String
    {
        return $this->values['allowedOrigins'];
    }

    public function getCacheType(): String
    {
        return $this->values['cacheType'];
    }

    public function getCachePath(): String
    {
        return $this->values['cachePath'];
    }

    public function getCacheTime(): int
    {
        return $this->values['cacheTime'];
    }

    public function getDebug(): String
    {
        return $this->values['debug'];
    }
}

// file: src/Tqd/PhpCrudApi/Request.php

class Request
{
    private $method;
    private $path;
    private $pathSegments;
    private $params;
    private $body;
    private $headers;

    public function __construct(String $method = null, String $path = null, String $query = null, String $body = null)
    {
        $this->parseMethod($method);
        $this->parsePath($path);
        $this->parseParams($query);
        $this->parseBody($body);
        $this->headers = array();
    }

    private function parseMethod(String $method = null)
    {
        if (!$method) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $method = $_SERVER['REQUEST_METHOD'];
            } else {
                $method = 'GET';
            }
        }
        $this->method = $method;
    }

    private function parsePath(String $path = null)
    {
        if (!$path) {
            if (isset($_SERVER['PATH_INFO'])) {
                $path = $_SERVER['PATH_INFO'];
            } else {
                $path = '/';
            }
        }
        $this->path = $path;
        $this->pathSegments = explode('/', $path);
    }

    private function parseParams(String $query = null)
    {
        if (!$query) {
            if (isset($_SERVER['QUERY_STRING'])) {
                $query = $_SERVER['QUERY_STRING'];
            } else {
                $query = '';
            }
        }
        $query = str_replace('[][]=', '[]=', str_replace('=', '[]=', $query));
        parse_str($query, $this->params);
    }

    private function parseBody(String $body = null)
    {
        if (!$body) {
            $body = file_get_contents('php://input');
        }
        $this->body = $body;
    }

    public function getMethod(): String
    {
        return $this->method;
    }

    public function getPath(): String
    {
        return $this->path;
    }

    public function getPathSegment(int $part): String
    {
        if ($part < 0 && $part >= count($this->pathSegments)) {
            return '';
        }
        return $this->pathSegments[$part];
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getBody() /*: ?array*/
    {
        $body = $this->body;
        $first = substr($body, 0, 1);
        if ($first == '[' || $first == '{') {
            $body = json_decode($body);
            $causeCode = json_last_error();
            if ($causeCode !== JSON_ERROR_NONE) {
                return null;
            }
        } else {
            parse_str($body, $input);
            foreach ($input as $key => $value) {
                if (substr($key, -9) == '__is_null') {
                    $input[substr($key, 0, -9)] = null;
                    unset($input[$key]);
                }
            }
            $body = (object) $input;
        }
        return $body;
    }

    public function addHeader(String $key, String $value = null)
    {
        if ($value === null) {
            $header = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            if (isset($_SERVER[$header])) {
                $value = $_SERVER[$header];
            }
        }
        if ($value !== null) {
            $this->headers[$key] = $value;
        }
    }

    public function getHeader(String $key): String
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        return '';
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public static function fromString(String $request): Request
    {
        $parts = explode("\n\n", trim($request), 2);
        $head = $parts[0];
        $body = isset($parts[1]) ? $parts[1] : null;
        $lines = explode("\n", $head);
        $line = explode(' ', trim(array_shift($lines)), 2);
        $method = $line[0];
        $url = isset($line[1]) ? $line[1] : '';
        $headers = array();
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        $request = new Request($method, $path, $query, $body);
        foreach ($lines as $line) {
            list($key, $value) = explode(':', $line, 2);
            $request->addHeader($key, trim($value));
        }
        return $request;
    }
}

// file: src/Tqd/PhpCrudApi/Response.php

class Response
{
    const OK = 200;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_FOUND = 404;
    const FORBIDDEN = 403;
    const NOT_ACCEPTABLE = 406;

    private $status;
    private $headers;
    private $body;

    public function __construct(int $status, $body)
    {
        $this->status = $status;
        $this->headers = array();
        $this->parseBody($body);
    }

    private function parseBody($body)
    {
        if ($body === '') {
            $this->body = '';
        } else {
            $data = json_encode($body, JSON_UNESCAPED_UNICODE);
            $this->addHeader('Content-Type', 'application/json');
            $this->addHeader('Content-Length', strlen($data));
            $this->body = $data;
        }
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): String
    {
        return $this->body;
    }

    public function addHeader(String $key, String $value)
    {
        $this->headers[$key] = $value;
    }

    public function getHeader(String $key): String
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        return null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function output()
    {
        http_response_code($this->getStatus());
        foreach ($this->getHeaders() as $key => $value) {
            header("$key: $value");
        }
        echo $this->getBody();
    }

    public function __toString(): String
    {
        $str = "$this->status\n";
        foreach ($this->headers as $key => $value) {
            $str .= "$key: $value\n";
        }
        if ($this->body !== '') {
            $str .= "\n";
            $str .= "$this->body\n";
        }
        return $str;
    }
}

// file: src/index.php

$config = new Config([
    'username' => 'php-crud-api',
    'password' => 'php-crud-api',
    'database' => 'php-crud-api',
]);
$request = new Request();
$api = new Api($config);
$response = $api->handle($request);
$response->output();
