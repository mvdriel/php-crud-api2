<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedTable {
    
    protected $columns;

    public function __construct(GenericMeta $meta, String $tableName) {
        $results = $meta->getTableColumns($tableName);
        foreach ($results as $result) {
            $columnName = $result['COLUMN_NAME'];
            $this->columns[$columnName] = $result;
        }
    }
}