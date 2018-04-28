<?php
namespace Com\Tqdev\CrudApi\Api;

class HabtmValues
{
    protected $pkValues;
    protected $fkValues;

    public function __construct(array $pkValues, array $fkValues)
    {
        $this->pkValues = $pkValues;
        $this->fkValues = $fkValues;
    }
}
