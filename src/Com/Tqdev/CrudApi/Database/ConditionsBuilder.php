<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;
use Com\Tqdev\CrudApi\Api\Condition\Condition;
use Com\Tqdev\CrudApi\Api\Condition\AndCondition;
use Com\Tqdev\CrudApi\Api\Condition\OrCondition;
use Com\Tqdev\CrudApi\Api\Condition\NotCondition;
use Com\Tqdev\CrudApi\Api\Condition\ColumnCondition;
use Com\Tqdev\CrudApi\Api\Condition\SpatialCondition;

class ConditionsBuilder {
    
    protected $driver;
    
    public function __construct(String $driver) {
        $this->driver = $driver;
    }

    protected function getConditionSql(Condition $condition, array &$arguments): String {
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
        throw new \Exception('Unknown Condition: '.get_class($condition));
    }

    protected function getAndConditionSql(AndCondition $and, array &$arguments): String {
        $parts = [];
        foreach ($and->getConditions() as $condition) {
            $parts[] = $this->getConditionSql($condition);
        }
        return '('.implode(' AND ', $parts).')';
    }

    protected function getOrConditionSql(OrCondition $or, array &$arguments): String {
        $parts = [];
        foreach ($or->getConditions() as $condition) {
            $parts[] = $this->getConditionSql($condition);
        }
        return '('.implode(' OR ', $parts).')';
    }

    protected function getNotConditionSql(NotCondition $not, array &$arguments): String {
        $condition = $not->getCondition();
        return '(NOT '.$this->getConditionSql($condition).')';
    }

    protected function quoteColumnName(ReflectedColumn $column): String {
        return '"'.$column->getName().'"';
    }

    protected function escapeLikeValue(String $value): String {
        return addcslashes($value,'%_');
    }

    protected function getColumnConditionSql(ColumnCondition $condition, array &$arguments): String {
        $column = $this->quoteColumnName($condition->getColumn());
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        switch($operator) {
            case 'cs': 
                $sql = "$column LIKE ?";
                $arguments[] = '%'.$this->escapeLikeValue($value).'%'; 
                break;
            case 'sw':
                $sql = "$column LIKE ?";
                $arguments[] = $this->escapeLikeValue($value).'%'; 
                break;
            case 'ew':
                $sql = "$column LIKE ?";
                $arguments[] = '%'.$this->escapeLikeValue($value); 
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
                }
                break;
            case 'in':
                $parts = explode(',', $value);
                $count = count($parts);
                if ($count>0) {
                    $qmarks = implode(',', explode('', str_repeat('?', $count)));
                    $sql = "$column IN ($qmarks)";
                    for ($i = 0; $i < $count; $i++) {
                        $arguments[] = $parts[$i];
                    }
                }
                break;
            case 'is':
                $sql = "$column IS NULL";
                break;
        }
        return $sql;
    }

    protected function getSpatialFunctionCall(String $name, String $arg1, String $arg2) {
        switch ($this->driver) {
            case 'mysql': 
            case 'pgsql': 
                return "$name($arg1, $arg2)=TRUE";
            case 'sql_srv': 
                $name = str_replace('_','',$name); 
                $arg2 = str_replace('ST_GeomFromText(?)','geometry::STGeomFromText(?,0)',$arg2);
                return "$arg1.$name($arg2)=1";
        }
    }

    protected function getSpatialConditionSql(ColumnCondition $condition, array &$arguments): String {
        $column = $this->quoteColumnName($condition->getColumn());
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        switch($operator) {
            case 'co': 
                $sql = $this->getSpatialFunctionCall('ST_Contains',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'cr': 
                $sql = $this->getSpatialFunctionCall('ST_Crosses',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'di': 
                $sql = $this->getSpatialFunctionCall('ST_Disjoint',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'eq': 
                $sql = $this->getSpatialFunctionCall('ST_Equals',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'in': 
                $sql = $this->getSpatialFunctionCall('ST_Intersects',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'ov': 
                $sql = $this->getSpatialFunctionCall('ST_Overlaps',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'to': 
                $sql = $this->getSpatialFunctionCall('ST_Touches',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'wi': 
                $sql = $this->getSpatialFunctionCall('ST_Within',$column,'ST_GeomFromText(?)');
                $arguments[] = $value;
                break;
            case 'ic': 
                $sql = $this->getSpatialFunctionCall('ST_IsClosed',$column,'');
                break;
            case 'is': 
                $sql = $this->getSpatialFunctionCall('ST_IsSimple',$column,'');
                break;
            case 'iv': 
                $sql = $this->getSpatialFunctionCall('ST_IsValid',$column,'');
                break;
        }
        return $sql;
    }

    public function getWhereClause(array $conditions, array &$arguments): String {
        if (count($conditions)==0) {
            return '';
        }
        $condition = AndCondition::fromArray($conditions);
        $sql = $this->getConditionSql($condition, $arguments);
        if ($sql != '') {
            return ' WHERE '.$sql;
        }
    }
}