<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedDatabase implements \JsonSerializable
{
    private $name;
    private $tables;

    public function __construct(String $name, array $tables)
    {
        $this->name = $name;
        $this->tables = [];
        foreach ($tables as $table) {
            $this->tables[$table->getName()] = $table;
        }
    }

    public static function fromMeta(GenericMeta $meta): ReflectedDatabase
    {
        $name = $meta->getDatabaseName();
        $tables = [];
        foreach ($meta->getTables() as $tableName) {
            if (in_array($tableName['TABLE_NAME'], $meta->getIgnoredTables())) {
                continue;
            }
            $table = ReflectedTable::fromMeta($meta, $tableName);
            $tables[$table->getName()] = $table;
        }
        return new ReflectedDatabase($name, array_values($tables));
    }

    public static function fromJson(object $json): ReflectedDatabase
    {
        $name = $json->name;
        $tables = [];
        if (isset($json->tables) && is_array($json->tables)) {
            foreach ($json->tables as $table) {
                $tables[] = ReflectedTable::fromJson($table);
            }
        }
        return new ReflectedDatabase($name, $tables);
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
