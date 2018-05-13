<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedTable implements \JsonSerializable
{
    private $name;
    private $columns;
    private $pk;
    private $fks;

    public function __construct(String $name, array $columns)
    {
        $this->name = $name;
        // set columns
        $this->columns = [];
        foreach ($columns as $column) {
            $columnName = $column->getName();
            $this->columns[$columnName] = $column;
        }
        // set primary key
        $this->pk = null;
        foreach ($columns as $column) {
            if ($column->getPk() == true) {
                $this->pk = $column;
            }
        }
        // set foreign keys
        $this->fks = [];
        foreach ($columns as $column) {
            $columnName = $column->getName();
            $referencedTableName = $column->getFk();
            if ($referencedTableName != '') {
                $this->fks[$columnName] = $referencedTableName;
            }
        }
    }

    public static function fromMeta(GenericMeta $meta, array $tableResult): ReflectedTable
    {
        $name = $tableResult['TABLE_NAME'];
        // set columns
        $columns = [];
        foreach ($meta->getTableColumns($name) as $tableColumn) {
            $column = ReflectedColumn::fromMeta($meta, $tableColumn);
            $columns[$column->getName()] = $column;
        }
        // set primary key
        $columnNames = $meta->getTablePrimaryKeys($name);
        if (count($columnNames) == 1) {
            $columnName = $columnNames[0];
            if (isset($columns[$columnName])) {
                $pk = $columns[$columnName];
                $pk->setPk(true);
            }
        }
        // set foreign keys
        $fks = $meta->getTableForeignKeys($name);
        foreach ($fks as $columnName => $table) {
            $columns[$columnName]->setFk($table);
        }
        return new ReflectedTable($name, array_values($columns));
    }

    public static function fromJson(object $json): ReflectedTable
    {
        $name = $json->name;
        $columns = [];
        if (isset($json->columns) && is_array($json->columns)) {
            foreach ($json->columns as $column) {
                $columns[] = ReflectedColumn::fromJson($column);
            }
        }
        return new ReflectedTable($name, $columns);
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
