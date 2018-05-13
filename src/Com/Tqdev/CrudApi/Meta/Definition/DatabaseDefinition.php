<?php
namespace Com\Tqdev\CrudApi\Meta\Definition;

class DatabaseDefinition implements \JsonSerializable
{
    private $name;
    private $tables;

    public function __construct(String $name, array $tables)
    {
        $this->name = $name;
        $this->tables = $tables;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'tables' => $this->tables,
        ];
    }
}
