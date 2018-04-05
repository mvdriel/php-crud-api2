<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericDB;

class ReflectedTable {
    
    protected $columns;

    public function __construct(GenericDB $db, String $tableName) {
        $results = $db->metaGetTableColumns($tableName);
        foreach ($results as $result) {
            $columnName = $result['COLUMN_NAME'];
            $this->columns[$columnName] = $result;
        }
    }
}