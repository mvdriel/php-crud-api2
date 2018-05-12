<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedDatabase implements \JsonSerializable
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

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'tables' => array_values($this->tables),
        ];
    }
}
