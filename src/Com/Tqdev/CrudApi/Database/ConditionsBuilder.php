<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;
use Com\Tqdev\CrudApi\Api\Condition\Condition;
use Com\Tqdev\CrudApi\Api\Condition\AndCondition;

class ConditionsBuilder {
    
    protected $driver;

    public function __construct(String $driver) {
        $this->driver = $driver;
    }

    protected function toSql(Condition $condition): String {
        return '1=1';
    }

    public function getWhereClause(array $conditions): String {
        if (count($conditions)==0) {
            return '';
        }
        $condition = AndCondition::fromArray($conditions);
        return ' WHERE '.$this->toSql($condition);
    }
}