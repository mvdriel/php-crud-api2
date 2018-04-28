<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

class NotCondition extends Condition
{
    protected $condition;

    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }

    public function getCondition(): array
    {
        return $this->condition;
    }
}
