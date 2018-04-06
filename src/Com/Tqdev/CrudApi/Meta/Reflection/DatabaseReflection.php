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
            $table = new ReflectedTable($meta, $result);
            $this->tables[$table->getName()] = $table;
        }
    }

    public function exists(String $tableName): bool {
        return isset($this->tables[$tableName]);
    }

}