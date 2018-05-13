<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;
use Com\Tqdev\CrudApi\Meta\Definition\DatabaseDefinition;

class ReflectedDatabase
{
    private $name;
    private $tables;

    public function __construct(GenericMeta $meta)
    {
        $this->name = $meta->getDatabaseName();
        $tableNames = $meta->getTables();
        foreach ($tableNames as $tableName) {
            if (in_array($tableName['TABLE_NAME'], $meta->getIgnoredTables())) {
                continue;
            }
            $table = new ReflectedTable($meta, $tableName);
            $this->tables[$table->getName()] = $table;
        }
    }

    public function exists(String $tableName): bool
    {
        return isset($this->tables[$tableName]);
    }

    public function get(String $tableName): ReflectedTable
    {
        return $this->tables[$tableName];
    }

    public function getTableNames(): array
    {
        return array_keys($this->tables);
    }

    public function toDefinition()
    {
        $tables = [];
        foreach ($this->tables as $table) {
            $tables[] = $table->toDefinition();
        }
        return new DatabaseDefinition($this->name, $tables);
    }
}
