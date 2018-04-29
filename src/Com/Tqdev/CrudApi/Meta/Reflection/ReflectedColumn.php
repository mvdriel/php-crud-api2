<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

class ReflectedColumn
{

    private $name;
    private $nullable;
    private $type;
    private $length;
    private $precision;
    private $scale;
    private $value;

    public function __construct(array $columnResult)
    {
        $this->name = $columnResult['COLUMN_NAME'];
        $this->nullable = $columnResult['IS_NULLABLE'];
        $this->type = $columnResult['DATA_TYPE'];
        $this->length = $columnResult['CHARACTER_MAXIMUM_LENGTH'];
        $this->precision = $columnResult['NUMERIC_PRECISION'];
        $this->scale = $columnResult['NUMERIC_SCALE'];
    }

    public function getName(): String
    {
        return $this->name;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getType(): String
    {
        return $this->type;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

}
