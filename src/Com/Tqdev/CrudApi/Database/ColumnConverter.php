<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;

class ColumnConverter
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    public function convertColumnValue(ReflectedColumn $column): String
    {
        if ($this->driver == 'pgsql' && $column->getType() == 'bytea') {
            return "decode(?, 'base64')";
        }
        if ($this->driver == 'mysql' && $column->getType() == 'blob') {
            return "FROM_BASE64(?)";
        }
        return '?';
    }

    public function convertColumnName(ReflectedColumn $column, $value): String
    {
        if ($this->driver == 'pgsql' && $column->getType() == 'bytea') {
            return "encode($value::bytea, 'base64') as $value";
        }
        if ($this->driver == 'mysql' && $column->getType() == 'blob') {
            return "TO_BASE64($value) as $value";
        }
        return $value;
    }

}
