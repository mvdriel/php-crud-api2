<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericDB;

class DatabaseReflection {

    protected $db;
    protected $tables;

    public function __construct(GenericDB $db) {
        $this->db = $db;
        $results = $db->metaGetTables();
        foreach ($results as $result) {
            $tableName = $result['TABLE_NAME'];
            $this->tables[$tableName] = new ReflectedTable($db, $tableName);
        }
    }

    public function exists(String $tableName): bool {
        return isset($this->tables[$tableName]);
    }

}