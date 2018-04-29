<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

class NoCondition extends Condition
{
    function  and (Condition $condition): Condition {
        return $condition;
    }

    function  or (Condition $condition): Condition {
        return $condition;
    }

    public function not(): Condition
    {
        return $this;
    }

}
