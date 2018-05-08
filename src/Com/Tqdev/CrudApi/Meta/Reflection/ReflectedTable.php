<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedTable implements \JsonSerializable
{

    private $name;
    private $columns;
    private $pk;
    private $fks;

    public function __construct(GenericMeta $meta, array $tableResult)
    {
        $this->name = $tableResult['TABLE_NAME'];
        $results = $meta->getTableColumns($this->name);
        foreach ($results as $result) {
            $column = new ReflectedColumn($meta, $result);
            $this->columns[$column->getName()] = $column;
        }
        $columnNames = $meta->getTablePrimaryKeys($this->name);
        if (count($columnNames) == 1) {
            $columnName = $columnNames[0];
            if (isset($this->columns[$columnName])) {
                $this->pk = $this->columns[$columnName];
                $this->pk->setPk(true);
            }
        }
        $this->fks = $meta->getTableForeignKeys($this->name);
        foreach ($this->fks as $columnName => $table) {
            $this->columns[$columnName]->setFk($table);
        }
    }

    public function exists(String $columnName): bool
    {
        return isset($this->columns[$columnName]);
    }

    public function getPk(): ReflectedColumn
    {
        return $this->pk;
    }

    public function getName(): String
    {
        return $this->name;
    }

    public function columnNames(): array
    {
        return array_keys($this->columns);
    }

    public function get($columnName): ReflectedColumn
    {
        return $this->columns[$columnName];
    }

    public function getFksTo(String $tableName): array
    {
        $columns = array();
        foreach ($this->fks as $columnName => $referencedTableName) {
            if ($tableName == $referencedTableName) {
                $columns[] = $this->columns[$columnName];
            }
        }
        return $columns;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'columns' => array_values($this->columns),
        ];
    }
}
