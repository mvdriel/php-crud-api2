<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class ColumnSelector {

	private static function isMandatoryField(String $tableName, String $fieldName, array $params): bool {
		return isset($params['mandatory']) && in_array($tableName . "." . $fieldName, $params['mandatory']);
	}

	private static function select(String $tableName, bool $primaryTable, array $params, String $paramName,
			array $columnNames, bool $include): array {
		if (!isset($params[$paramName])) {
			return $columnNames;
		}
		$columns = array();
		foreach (explode(',',$params[$paramName][0]) as $key) {
			$columns[$key] = true;
		}
		$result = array();
		foreach ($columnNames as $key) {
			$match = isset($columns['*.*']);
			if (!$match) {
				$match = isset($columns[$tableName . '.*']) || isset($columns[$tableName . '.' . $key]);
			}
			if ($primaryTable && !$match) {
				$match = isset($columns['*']) || isset($columns[$key]);
			}
			if ($match) {
				if ($include || self::isMandatoryField($tableName, $key, $params)) {
					$result[] = $key;
				}
			} else {
				if (!$include || self::isMandatoryField($tableName, $key, $params)) {
					$result[] = $key;
				}
			}
		}
		return $result;
    }
    
    private static function columns(ReflectedTable $table, bool $primaryTable, array $params):array {
		$tableName = $table->getName();
		$results = $table->columnNames();
		$results = self::select($tableName, $primaryTable, $params, 'columns', $results, true);
		$results = self::select($tableName, $primaryTable, $params, 'exclude', $results, false);
		return $results;
    }
    
	public static function columnNames(ReflectedTable $table, bool $primaryTable, array $params): array {
		$columns = array();
		foreach (self::columns($table, $primaryTable, $params) as $key) {
			$columns[] = $table->get($key);
		}
		return $columns;
	}
}