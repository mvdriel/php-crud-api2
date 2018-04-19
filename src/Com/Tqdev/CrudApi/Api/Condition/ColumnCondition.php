<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;

class ColumnCondition extends Condition
{
    protected $column;
    protected $operator;
    protected $value;
    
    public function __construct(ReflectedColumn $column, String $operator, String $value) {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getColumn(): ReflectedColumn {
        return $this->column;
    }

    public function getOperator(): String {
        return $this->operator;
    }

    public function getValue(): String {
        return $this->value;
    }
}