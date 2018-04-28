<?php
namespace Com\Tqdev\CrudApi\Api\Condition;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

abstract class Condition
{
    function  and (Condition $condition): Condition {
        return new AndCondition($this, $condition);
    }

    function  or (Condition $condition): Condition {
        return new OrCondition($this, $condition);
    }

    public function not(): Condition
    {
        return new NotCondition($this);
    }

    public static function fromString(ReflectedTable $table, String $value) /*: ?Condition*/
    {
        $condition = null;
        $parts = explode(',', $value, 3);
        if (count($parts) < 2) {
            return null;
        }
        $field = $table->get($parts[0]);
        $command = $parts[1];
        $negate = false;
        $spatial = false;
        if (strlen($command) > 2) {
            if (substr($command, 0, 1) == 'n') {
                $negate = true;
                $command = substr($command, 1);
            }
            if (substr($command, 0, 1) == 's') {
                $spatial = true;
                $command = substr($command, 1);
            }
        }
        if (count($parts) == 3 || (count($parts) == 2 && in_array($command, ['ic', 'is', 'iv']))) {
            if ($spatial) {
                if (in_array($command, ['co', 'cr', 'di', 'eq', 'in', 'ov', 'to', 'wi', 'ic', 'is', 'iv'])) {
                    $condition = new SpatialCondition($field, $command, $parts[2]);
                }
            } else {
                if (in_array($command, ['cs', 'sw', 'ew', 'eq', 'lt', 'le', 'ge', 'gt', 'bt', 'in', 'is'])) {
                    $condition = new ColumnCondition($field, $command, $parts[2]);
                }
            }
        }
        if ($condition != null) {
            if ($negate) {
                $condition = $condition->not();
            }
        }
        return $condition;
    }

}
