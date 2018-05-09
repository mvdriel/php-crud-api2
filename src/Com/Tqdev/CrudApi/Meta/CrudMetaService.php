<?php
namespace Com\Tqdev\CrudApi\Meta;

use Com\Tqdev\CrudApi\Cache\Cache;
use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\Reflection\DatabaseReflection;

class CrudMetaService
{
    private $db;
    private $cache;
    private $tables;

    public function __construct(GenericDB $db, Cache $cache, int $ttl)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->tables = $this->cache->get('DatabaseReflection');
        if ($this->tables === null) {
            $this->tables = new DatabaseReflection($db->meta());
            $this->cache->set('DatabaseReflection', $this->tables, $ttl);
        }
    }

    public function getDatabaseReflection(): DatabaseReflection
    {
        return $this->tables;
    }
}
