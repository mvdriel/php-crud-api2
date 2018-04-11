<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedTable {
    
    protected $name;
    protected $columns;
    protected $pk;
    protected $fks;

    public function __construct(GenericMeta $meta, array $tableResult) {
        $this->name = $tableResult['TABLE_NAME'];
        $results = $meta->getTableColumns($this->name);
        foreach ($results as $result) {
            $column = new ReflectedColumn($result);
            $this->columns[$column->getName()] = $column;
        }
        $columnNames = $meta->getTablePrimaryKeys($this->name);
        if (count($columnNames)==1) {
            $columnName = $columnNames[0];
            if (isset($this->columns[$columnName])) {
                $this->pk = $this->columns[$columnName];
            }
        }
        $this->fks = $meta->getTableForeignKeys($this->name);
    }

    public function exists(String $columnName): bool {
        return isset($this->columns[$columnName]);
    }

    public function getPk(): ReflectedColumn {
        return $this->pk;
    }

    public function getName(): String {
        return $this->name;
    }

    public function columnNames(): array {
        return array_keys($this->columns);
    }

    public function get($columnName): ReflectedColumn {
        return $this->columns[$columnName];
    }
}