<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

class OrCondition extends Condition
{
    protected $conditions;

    public function __construct(Condition $condition1, Condition $condition2)
    {
        $this->conditions = [$condition1, $condition2];
    }

    function  or (Condition $condition): Condition{
        $this->conditions[] = $condition;
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public static function fromArray(array $conditions) /*: ?Condition*/
    {
        if (count($conditions) == 0) {
            return null;
        }
        if (count($conditions) == 1) {
            return $conditions[0];
        }
        $condition = new OrCondition($conditions[0], $conditions[1]);
        for ($i = 2; $i < count($conditions); $i++) {
            $condition = $condition->or($conditions[$i]);
        }
        return $condition;
    }
}
