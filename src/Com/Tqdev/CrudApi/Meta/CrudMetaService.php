<?php
namespace Com\Tqdev\CrudApi\Meta;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\Reflection\DatabaseReflection;

class CrudMetaService
{
    private $db;

    private $tables;

    public function __construct(GenericDB $db)
    {
        $this->db = $db;
        $filename = 'reflection_cache.json';
        if (file_exists($filename)) {
            $this->tables = unserialize(file_get_contents($filename));
        } else {
            $this->tables = new DatabaseReflection($db->meta());
            file_put_contents($filename, serialize($this->tables));
        }
    }

    public function getDatabaseReflection(): DatabaseReflection
    {
        return $this->tables;
    }
}
