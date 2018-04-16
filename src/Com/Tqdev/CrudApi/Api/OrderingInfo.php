<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class OrderingInfo {

    public function getSortFields(ReflectedTable $table, array $params): array {
		$fields = array();
		if (isset($params['order'])) {
			foreach ($params['order'] as $key) {
                $parts = explode(',', $key, 3);
                $columnName = $parts[0];
                if (!$table->exists($columnName)) {
                    continue;
                }
			    $ascending = 'ASC';
			    if (count($parts) > 1) {
				    if (substr(strtoupper($parts[1]),0,4)=="DESC") {
                        $ascending = 'DESC';
                    }
                }
                $fields[] = [$columnName, $ascending];
			}
		} else {
            $fields[] = [$table->getPk()->getName(), 'ASC'];
        }
		return $fields;
    }
}
