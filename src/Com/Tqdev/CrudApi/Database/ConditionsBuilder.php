<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Api\Condition\AndCondition;
use Com\Tqdev\CrudApi\Api\Condition\BooleanCondition;
use Com\Tqdev\CrudApi\Api\Condition\ColumnCondition;
use Com\Tqdev\CrudApi\Api\Condition\Condition;
use Com\Tqdev\CrudApi\Api\Condition\NotCondition;
use Com\Tqdev\CrudApi\Api\Condition\OrCondition;
use Com\Tqdev\CrudApi\Api\Condition\SpatialCondition;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;

class ConditionsBuilder
{

    protected $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    protected function getConditionSql(Condition $condition, array &$arguments): String
    {
        if ($condition instanceof AndCondition) {
            return $this->getAndConditionSql($condition, $arguments);
        }
        if ($condition instanceof OrCondition) {
            return $this->getOrConditionSql($condition, $arguments);
        }
        if ($condition instanceof NotCondition) {
            return $this->getNotConditionSql($condition, $arguments);
        }
        if ($condition instanceof ColumnCondition) {
            return $this->getColumnConditionSql($condition, $arguments);
        }
        if ($condition instanceof SpatialCondition) {
            return $this->getSpatialConditionSql($condition, $arguments);
        }
        if ($condition instanceof BooleanCondition) {
            return $this->getBooleanConditionSql($condition, $arguments);
        }
        throw new \Exception('Unknown Condition: ' . get_class($condition));
    }

    protected function getAndConditionSql(AndCondition $and, array &$arguments): String
    {
        $parts = [];
        foreach ($and->getConditions() as $condition) {
            $parts[] = $this->getConditionSql($condition, $arguments);
        }
        return '(' . implode(' AND ', $parts) . ')';
    }

    protected function getOrConditionSql(OrCondition $or, array &$arguments): String
    {
        $parts = [];
        foreach ($or->getConditions() as $condition) {
            $parts[] = $this->getConditionSql($condition, $arguments);
        }
        return '(' . implode(' OR ', $parts) . ')';
    }

    protected function getNotConditionSql(NotCondition $not, array &$arguments): String
    {
        $condition = $not->getCondition();
        return '(NOT ' . $this->getConditionSql($condition, $arguments) . ')';
    }

    protected function quoteColumnName(ReflectedColumn $column): String
    {
        return '"' . $column->getName() . '"';
    }

    protected function escapeLikeValue(String $value): String
    {
        return addcslashes($value, '%_');
    }

    protected function getColumnConditionSql(ColumnCondition $condition, array &$arguments): String
    {
        $column = $this->quoteColumnName($condition->getColumn());
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        switch ($operator) {
            case 'cs':
                $sql = "$column LIKE ?";
                $arguments[] = '%' . $this->escapeLikeValue($value) . '%';
                break;
            case 'sw':
                $sql = "$column LIKE ?";
                $arguments[] = $this->escapeLikeValue($value) . '%';
                break;
            case 'ew':
                $sql = "$column LIKE ?";
                $arguments[] = '%' . $this->escapeLikeValue($value);
                break;
            case 'eq':
                $sql = "$column = ?";
                $arguments[] = $value;
                break;
            case 'lt':
                $sql = "$column < ?";
                $arguments[] = $value;
                break;
            case 'le':
                $sql = "$column <= ?";
                $arguments[] = $value;
                break;
            case 'ge':
                $sql = "$column >= ?";
                $arguments[] = $value;
                break;
            case 'gt':
                $sql = "$column > ?";
                $arguments[] = $value;
                break;
            case 'bt':
                $parts = explode(',', $value, 2);
                $count = count($parts);
                if ($count == 2) {
                    $sql = "($column >= ? AND $column <= ?)";
                    $arguments[] = $parts[0];
                    $arguments[] = $parts[1];
                } else {
                    $sql = "FALSE";
                }
                break;
            case 'in':
                $parts = explode(',', $value);
                $count = count($parts);
                if ($count > 0) {
                    $qmarks = implode(',', str_split(str_repeat('?', $count)));
                    $sql = "$column IN ($qmarks)";
                    for ($i = 0; $i < $count; $i++) {
                        $arguments[] = $parts[$i];
                    }
                } else {
                    $sql = "FALSE";
                }
                break;
            case 'is':
                $sql = "$column IS NULL";
                break;
        }
        return $sql;
    }

    protected function getSpatialFunctionName(String $operator): String
    {
        switch ($operator) {
            case 'co':return 'ST_Contains';
            case 'cr':return 'ST_Crosses';
            case 'di':return 'ST_Disjoint';
            case 'eq':return 'ST_Equals';
            case 'in':return 'ST_Intersects';
            case 'ov':return 'ST_Overlaps';
            case 'to':return 'ST_Touches';
            case 'wi':return 'ST_Within';
            case 'ic':return 'ST_IsClosed';
            case 'is':return 'ST_IsSimple';
            case 'iv':return 'ST_IsValid';
        }
    }

    protected function hasSpatialArgument(String $operator): bool
    {
        return in_array($opertor, ['ic', 'is', 'iv']) ? false : true;
    }

    protected function getSpatialFunctionCall(String $functionName, String $column, bool $hasArgument): String
    {
        $argument = $hasArgument ? 'ST_GeomFromText(?)' : '';
        switch ($this->driver) {
            case 'mysql':
            case 'pgsql':
                return "$functionName($column, $argument)=TRUE";
            case 'sql_srv':
                $functionName = str_replace('_', '', $functionName);
                $argument = str_replace('ST_GeomFromText(?)', 'geometry::STGeomFromText(?,0)', $argument);
                return "$column.$functionName($argument)=1";
        }
    }

    protected function getSpatialConditionSql(ColumnCondition $condition, array &$arguments): String
    {
        $column = $this->quoteColumnName($condition->getColumn());
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        $functionName = $this->getSpatialFunctionName($operator);
        $hasArgument = $this->hasSpatialArgument($operator);
        $sql = $this->getSpatialFunctionCall($functionName, $column, $hasArgument);
        if ($hasArgument) {
            $arguments[] = $value;
        }
        return $sql;
    }

    protected function getBooleanConditionSql(BooleanCondition $condition, array &$arguments): String
    {
        $value = $condition->getValue();
        return $value ? 'TRUE' : 'FALSE';
    }

    public function getWhereClause(array $conditions, array &$arguments): String
    {
        if (count($conditions) == 0) {
            return '';
        }
        $condition = AndCondition::fromArray($conditions);
        $sql = $this->getConditionSql($condition, $arguments);
        if ($sql != '') {
            return ' WHERE ' . $sql;
        }
    }
}
