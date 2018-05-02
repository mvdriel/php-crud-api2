<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;

class ColumnConverter
{
    private $driver;
    private $types;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
        $this->types = new TypeInfo($driver);
    }

    public function convertColumnValue(ReflectedColumn $column): String
    {
        if ($this->types->isBinary($column)) {
            switch ($this->driver) {
                case 'mysql':
                    return "FROM_BASE64(?)";
                case 'pgsql':
                    return "decode(?, 'base64')";
            }
        }
        if ($this->types->isGeometry($column)) {
            return "ST_GeomFromText(?)";
        }
        return '?';
    }

    public function convertColumnName(ReflectedColumn $column, $value): String
    {
        if ($this->types->isBinary($column)) {
            switch ($this->driver) {
                case 'mysql':
                    return "TO_BASE64($value) as $value";
                case 'pgsql':
                    return "encode($value::bytea, 'base64') as $value";
            }
        }
        if ($this->types->isGeometry($column)) {
            return "ST_AsText($value) as $value";
        }
        return $value;
    }

}
