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
        $this->tables = new DatabaseReflection($db->meta());
    }

    public function getDatabaseReflection(): DatabaseReflection
    {
        return $this->tables;
    }
}
