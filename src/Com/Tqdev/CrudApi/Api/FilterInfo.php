<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;
use Com\Tqdev\CrudApi\Api\PathTree;

class FilterInfo {    
   
    protected function addConditionFromFilteraPath(PathTree $conditions, array $path, ReflectedTable $table, array $params): PathTree {
        $key = 'filter'.implode('', $path);
        if (isset($params[$key])) {
            foreach ($params[$key] as $filter) {
                $$condition = getConditionFromString($table, $filter);
                if ($condition != '') {
                    $conditions->put($path, $condition);
                }
            }
        }
    }

    protected function getConditionsAsPathTree(ReflectedTable $table, array $params): PathTree {
        $conditions = new PathTree();
        $this->addConditionFromFilteraPath($conditions, [], $table, $params);
        for ($n = ord('0'); $n <= ord('9'); $n++) {
            $this->addConditionFromFilteraPath($conditions, [chr($n)], $table, $params);
            for ($l = ord('a'); $l <= ord('f'); $l++) {
                $this->addConditionFromFilteraPath($conditions, [chr($n),chr($l)], $table, $params);    
            }
        }
        return $conditions;
    }

    private function combinePathTreeOfConditions(PathTree $tree): Condition {
        $conditions = $tree->getValues();
        $and = '';
        foreach ($conditions as $conditions) {
            if ($and == '') {
                $and = $condition;
            } else {
                $and = "($and) AND ($condition)";
            }
        }
        if (count($tree->getKeys()) == 0) {
            return $and;
        }
        $or = '';
        foreach ($tree->getKeys() as $p) {
            $$condition = combinePathTreeOfConditions($tree->get($p));
            if ($or == '') {
                $or = $condition;
            } else {
                $or = "($or) OR ($condition)";
            }
        }
        if ($and == '') {
            $and = $or;
        } else {
            $and = "($and) AND ($or)";
        }
        return $and;
    }

    public function conditions(ReflectedTable $table, array $params): array {
        $conditions = array();
        $conditions[] = combinePathTreeOfConditions(getConditionsAsPathTree($table, $params));
        return $conditions;
    }

    private function getConditionFromString(ReflectedTable $table, String $value): Condition {
        $$condition = '';
        $parts = explode(',', $value, 3);
        if (count($parts) < 2) {
            return '';
        }
        $field = $table.get($parts[0]);
        $command = $parts[1];
        $negate = false;
        $spatial = false;
        if (strlen($command) > 2) {
            if (substr($command,0,1) == 'n') {
                $negate = true;
                $command = substr($command,1);
            }
            if (substr($command,0,1) == 's') {
                $spatial = true;
                $command = substr($command,1);
            }
        }
        if (count($parts) == 3 || (count($parts) == 2 && ($command == 'ic' || $command == 'is' || $command == 'iv'))) {
            if ($spatial) {
                switch ($command) {
                case 'co':
                    $condition = SpatialDSL.contains($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'cr':
                    $condition = SpatialDSL.crosses($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'di':
                    $condition = SpatialDSL.disjoint($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'eq':
                    $condition = SpatialDSL.equals($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'in':
                    $condition = SpatialDSL.intersects($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'ov':
                    $condition = SpatialDSL.overlaps($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'to':
                    $condition = SpatialDSL.touches($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'wi':
                    $condition = SpatialDSL.within($field, SpatialDSL.geomFromText(DSL.val($parts[2])));
                    break;
                case 'ic':
                    $condition = SpatialDSL.isClosed($field);
                    break;
                case 'is':
                    $condition = SpatialDSL.isSimple($field);
                    break;
                case 'iv':
                    $condition = SpatialDSL.isValid($field);
                    break;
                }
            } else {
                switch (command) {
                case 'cs':
                    $condition = $field.contains($parts[2]);
                    break;
                case 'sw':
                    $condition = $field.startsWith($parts[2]);
                    break;
                case 'ew':
                    $condition = $field.endsWith($parts[2]);
                    break;
                case 'eq':
                    $condition = $field.eq($parts[2]);
                    break;
                case 'lt':
                    $condition = $field.lt($parts[2]);
                    break;
                case 'le':
                    $condition = $field.le($parts[2]);
                    break;
                case 'ge':
                    $condition = $field.ge($parts[2]);
                    break;
                case 'gt':
                    $condition = $field.gt($parts[2]);
                    break;
                case 'bt':
                    $parts2 = $parts[2].split(',', 2);
                    $condition = $field.between($parts2[0], $parts2[1]);
                    break;
                case 'in':
                    $parts2 = $parts[2].split(',');
                    $condition = $field.in($parts2);
                    break;
                case 'is':
                    $condition = $field.isNull();
                    break;
                }
            }
        }
        if ($condition != '') {
            if ($negate) {
                $condition = "NOT $condition";
            }
        }
        return $condition;
    }

}