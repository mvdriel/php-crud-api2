<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

use Com\Tqdev\CrudApi\Api\Condition\NoCondition;

class AndCondition extends Condition
{
    private $conditions;

    public function __construct(Condition $condition1, Condition $condition2)
    {
        $this->conditions = [$condition1, $condition2];
    }

    public function _and(Condition $condition): Condition
    {
        $this->conditions[] = $condition;
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public static function fromArray(array $conditions): Condition
    {
        if (count($conditions) == 0) {
            return new NoCondition();
        }
        if (count($conditions) == 1) {
            return $conditions[0];
        }
        $condition = new AndCondition($conditions[0], $conditions[1]);
        for ($i = 2; $i < count($conditions); $i++) {
            $condition = $condition->_and($conditions[$i]);
        }
        return $condition;
    }
}
