<?php
namespace Com\Tqdev\CrudApi\Meta;

use Com\Tqdev\CrudApi\Cache\Cache;
use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedDatabase;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class MetaService
{
    private $db;
    private $cache;
    private $tables;

    public function __construct(GenericDB $db, Cache $cache, int $ttl)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->tables = $this->cache->get('ReflectedDatabase');
        if ($this->tables === null) {
            $this->tables = ReflectedDatabase::fromMeta($db->meta());
            $this->cache->set('ReflectedDatabase', $this->tables, $ttl);
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
