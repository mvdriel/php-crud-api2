<?php
namespace Com\Tqdev\CrudApi\Meta;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\Reflection\DatabaseReflection;

class CrudMetaService
{

    protected $db;

    public function __construct(GenericDB $db)
    {
        $this->db = $db;
    }

    public function getDatabaseReflection(): DatabaseReflection
    {
        return new DatabaseReflection($this->db->meta());
    }
}
