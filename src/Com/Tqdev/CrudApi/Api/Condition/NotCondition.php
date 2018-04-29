<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

class NotCondition extends Condition
{
    private $condition;

    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }

    public function getCondition(): array
    {
        return $this->condition;
    }
}
