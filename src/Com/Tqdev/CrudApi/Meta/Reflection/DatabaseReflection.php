<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class DatabaseReflection {

    protected $meta;
    protected $tables;

    public function __construct(GenericMeta $meta) {
        $this->meta = $meta;
        $results = $meta->getTables();
        foreach ($results as $result) {
            $tableName = $result['TABLE_NAME'];
            $this->tables[$tableName] = new ReflectedTable($meta, $tableName);
        }
    }

    public function exists(String $tableName): bool {
        return isset($this->tables[$tableName]);
    }

}