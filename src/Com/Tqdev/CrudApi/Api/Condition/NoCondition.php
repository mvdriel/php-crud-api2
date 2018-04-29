<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

class NoCondition extends Condition
{
    public function _and(Condition $condition): Condition
    {
        return $condition;
    }

    public function _or(Condition $condition): Condition
    {
        return $condition;
    }

    public function not(): Condition
    {
        return $this;
    }

}
