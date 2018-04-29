<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class TypeConverter
{

    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    private function needsBooleanConversion(ReflectedColumn $column): bool
    {
        switch ($this->driver) {
            case 'mysql':return $column->getType() == 'bit';
        }
        return false;
    }

    private function convertToBoolean($value): bool
    {
        return $value ? true : false;
    }

    public function convertRecords(ReflectedTable $table, array $columnNames, array &$records)
    {
        foreach ($columnNames as $columnName) {
            $column = $table->get($columnName);
            if ($this->needsBooleanConversion($column)) {
                foreach ($records as $i => $record) {
                    $value = $records[$i][$columnName];
                    if ($value === null) {
                        continue;
                    }
                    $records[$i][$columnName] = $this->convertToBoolean($value);
                }
            }
        }
    }

}
