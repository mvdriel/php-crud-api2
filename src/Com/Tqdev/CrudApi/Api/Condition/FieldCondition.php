<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;

class FieldCondition extends Condition
{
    protected $column;
    protected $operand;
    protected $value;
    
    public function __construct(ReflectedColumn $column, String $operand, String $value) {
        $this->column = $column;
        $this->operand = $operand;
        $this->value = $value;
    }

    public function getColumn(): ReflectedColumn {
        return $this->column;
    }

    public function getOperand(): String {
        return $this->operand;
    }

    public function getValue(): String {
        return $this->value;
    }
}