<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class OrderingInfo {

    public function directions(ReflectedTable $table, array $params): array {
		$fields = array();
		if (isset($params['order'])) {
			foreach ($params['order'] as $key) {
				$parts = explode(',', $key, 3);
				$columnName = $table->get($parts[0])->getName();
				$ascending = true;
				if (count($parts) > 1) {
					$ascending = substr(strtolower($parts[1]),0,4)!="desc";
                }
                $fields[$columnName] = $ascending;
			}
		} else {
            $fields[$table->getPk()->getName()] = true;
        }
		return $fields;
    }
}
