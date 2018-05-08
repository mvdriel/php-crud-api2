<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class DatabaseReflection implements \JsonSerializable
{

    private $meta;
    private $tables;

    public function __construct(GenericMeta $meta)
    {
        $this->meta = $meta;
        $tableNames = $meta->getTables();
        foreach ($tableNames as $tableName) {
            if ($tableName['TABLE_NAME'] == 'spatial_ref_sys') {
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
        return ['tables' => array_values($this->tables)];
    }
}
