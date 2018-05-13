<?php
namespace Com\Tqdev\CrudApi\Meta\Definition;

class ColumnDefinition implements \JsonSerializable
{
    private $name;
    private $type;
    private $length;
    private $precision;
    private $scale;
    private $nullable;
    private $pk;
    private $fk;

    public function __construct(String $name, String $type, int $length, int $precision, int $scale, bool $nullable, bool $pk, String $fk)
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->nullable = $nullable;
        $this->pk = $pk;
        $this->fk = $fk;
    }

    public function jsonSerialize()
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'nullable' => $this->nullable,
            'pk' => $this->pk,
            'fk' => $this->fk,
        ]);
    }
}
