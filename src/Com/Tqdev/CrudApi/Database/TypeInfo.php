<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;

class TypeInfo
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    public function isBinary(ReflectedColumn $column): bool
    {
        switch ($this->driver) {
            case 'mysql':
                return in_array($column->getType(), ['tinyblob', 'blob', 'mediumblob', 'longblob', 'varbinary', 'binary']);
            case 'pgsql':
                return in_array($column->getType(), ['bytea']);
        }
        return false;
    }

    public function isBoolean(ReflectedColumn $column): bool
    {
        switch ($this->driver) {
            case 'mysql':
                return in_array($column->getType(), ['bit']);
        }
        return false;
    }
}
