<?php
namespace Com\Tqdev\CrudApi\Meta\Definition;

class TableDefinition implements \JsonSerializable
{
    private $name;
    private $columns;

    public function __construct(String $name, array $columns)
    {
        $this->name = $name;
        $this->columns = $columns;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'columns' => $this->columns,
        ];
    }
}
