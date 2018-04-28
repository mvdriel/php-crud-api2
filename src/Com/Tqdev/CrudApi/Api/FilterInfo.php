<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Api\Condition\AndCondition;
use Com\Tqdev\CrudApi\Api\Condition\Condition;
use Com\Tqdev\CrudApi\Api\Condition\OrCondition;
use Com\Tqdev\CrudApi\Api\PathTree;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class FilterInfo
{

    protected function addConditionFromFilterPath(PathTree $conditions, array $path, ReflectedTable $table, array $params)
    {
        $key = 'filter' . implode('', $path);
        if (isset($params[$key])) {
            foreach ($params[$key] as $filter) {
                $condition = Condition::fromString($table, $filter);
                if ($condition != null) {
                    $conditions->put($path, $condition);
                }
            }
        }
    }

    protected function getConditionsAsPathTree(ReflectedTable $table, array $params): PathTree
    {
        $conditions = new PathTree();
        $this->addConditionFromFilterPath($conditions, [], $table, $params);
        for ($n = ord('0'); $n <= ord('9'); $n++) {
            $this->addConditionFromFilterPath($conditions, [chr($n)], $table, $params);
            for ($l = ord('a'); $l <= ord('f'); $l++) {
                $this->addConditionFromFilterPath($conditions, [chr($n), chr($l)], $table, $params);
            }
        }
        return $conditions;
    }

    private function combinePathTreeOfConditions(PathTree $tree) /*: ?Condition*/
    {
        $andConditions = $tree->getValues();
        $and = AndCondition::fromArray($andConditions);
        $orConditions = [];
        foreach ($tree->getKeys() as $p) {
            $orConditions[] = $this->combinePathTreeOfConditions($tree->get($p));
        }
        $or = OrCondition::fromArray($orConditions);
        if ($and == null) {
            $and = $or;
        } else {
            if ($or != null) {
                $and = $and->and($or);
            }
        }
        return $and;
    }

    public function getConditions(ReflectedTable $table, array $params): array
    {
        $conditions = array();
        $condition = $this->combinePathTreeOfConditions($this->getConditionsAsPathTree($table, $params));
        if ($condition != null) {
            $conditions[] = $condition;
        }
        return $conditions;
    }

}
