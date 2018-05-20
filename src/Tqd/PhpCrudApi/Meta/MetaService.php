<?php
namespace Tqd\PhpCrudApi\Meta;

use Tqd\PhpCrudApi\Cache\Cache;
use Tqd\PhpCrudApi\Database\GenericDB;
use Tqd\PhpCrudApi\Meta\Reflection\ReflectedDatabase;
use Tqd\PhpCrudApi\Meta\Reflection\ReflectedTable;

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
