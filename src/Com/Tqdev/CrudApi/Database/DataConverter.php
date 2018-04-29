<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class DataConverter
{
    private $driver;
    private $types;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
        $this->types = new TypeInfo($driver);
    }

    private function convertRecordValue($conversion, $value)
    {
        switch ($conversion) {
            case 'boolean':
                return $value ? true : false;
        }
        return $value;
    }

    private function getRecordValueConversion(ReflectedColumn $column): String
    {
        if ($this->driver == 'mysql' && $this->types->isBoolean($column)) {
            return 'boolean';
        }
        return '';
    }

    public function convertRecords(ReflectedTable $table, array $columnNames, array &$records): void
    {
        foreach ($columnNames as $columnName) {
            $column = $table->get($columnName);
            $conversion = $this->getRecordValueConversion($column);
            if ($conversion != '') {
                foreach ($records as $i => $record) {
                    $value = $records[$i][$columnName];
                    if ($value === null) {
                        continue;
                    }
                    $records[$i][$columnName] = $this->convertRecordValue($conversion, $value);
                }
            }
        }
    }

    private function convertInputValue($conversion, $value)
    {
        switch ($conversion) {
            case 'base64url_to_base64':
                return str_pad(strtr($value, '-_', '+/'), ceil(strlen($value) / 4) * 4, '=', STR_PAD_RIGHT);
        }
        return $value;
    }

    private function getInputValueConversion(ReflectedColumn $column): String
    {
        if ($this->types->isBinary($column)) {
            return 'base64url_to_base64';
        }
        return '';
    }

    public function convertColumnValues(ReflectedTable $table, array &$columnValues): void
    {
        $columnNames = array_keys($columnValues);
        foreach ($columnNames as $columnName) {
            $column = $table->get($columnName);
            $conversion = $this->getInputValueConversion($column);
            if ($conversion != '') {
                $value = $columnValues[$columnName];
                if ($value !== null) {
                    $columnValues[$columnName] = $this->convertInputValue($conversion, $value);
                }
            }
        }
    }
}
