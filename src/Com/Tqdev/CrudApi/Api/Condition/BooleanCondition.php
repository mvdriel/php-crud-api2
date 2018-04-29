<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

class BooleanCondition extends Condition
{
    protected $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
