<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;
use Com\Tqdev\CrudApi\Database\ColumnConverter;

class ColumnSelector {

	protected function isMandatory(String $tableName, String $columnName, array $params): bool {
		return isset($params['mandatory']) && in_array($tableName . "." . $columnName, $params['mandatory']);
	}

	protected function select(String $tableName, bool $primaryTable, array $params, String $paramName,
			array $columnNames, bool $include): array {
		if (!isset($params[$paramName])) {
			return $columnNames;
		}
		$columns = array();
		foreach (explode(',',$params[$paramName][0]) as $columnName) {
			$columns[$columnName] = true;
		}
		$result = array();
		foreach ($columnNames as $columnName) {
			$match = isset($columns['*.*']);
			if (!$match) {
				$match = isset($columns[$tableName . '.*']) || isset($columns[$tableName . '.' . $columnName]);
			}
			if ($primaryTable && !$match) {
				$match = isset($columns['*']) || isset($columns[$columnName]);
			}
			if ($match) {
				if ($include || $this->isMandatory($tableName, $columnName, $params)) {
					$result[] = $columnName;
				}
			} else {
				if (!$include || $this->isMandatory($tableName, $columnName, $params)) {
					$result[] = $columnName;
				}
			}
		}
		return $result;
    }
    
    public function getNames(ReflectedTable $table, bool $primaryTable, array $params): array {
		$tableName = $table->getName();
		$results = $table->columnNames();
		$results = $this->select($tableName, $primaryTable, $params, 'columns', $results, true);
		$results = $this->select($tableName, $primaryTable, $params, 'exclude', $results, false);
		return $results;
	}

	public function getValues(ReflectedTable $table, bool $primaryTable, array $record, array $params): array {
		$results = array();
		$columnNames = $this->getNames($table, $primaryTable, $params);
		foreach ($columnNames as $columnName) {
			if (isset($record[$columnName])) {
                $results[$columnName] = $record[$columnName];
            }
		}
		return $results;
    }
    
}