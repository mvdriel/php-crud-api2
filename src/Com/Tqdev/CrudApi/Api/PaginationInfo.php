<?php
namespace Com\Tqdev\CrudApi\Api;

class PaginationInfo {

	public $DEFAULT_PAGE_SIZE = 20;

	public function hasPage(array $params): bool {
		return isset($params['page']);
	}

	public function pageOffset(array $params): int {
		$offset = 0;
		$pageSize = $this->pageSize($params);
		if (isset($params['page'])) {
			foreach ($params['page'] as $key) {
				$parts = explode(',', $key, 2);
				$page = intval($parts[0]) - 1;
				$offset = $page * $pageSize;
			}
		}
		return $offset;
	}

	public function pageSize(array $params): int {
		$pageSize = $this->DEFAULT_PAGE_SIZE;
		if (isset($params['page'])) {
			foreach ($params['page'] as $key) {
				$parts = explode(',', $key, 2);
				if (count($parts) > 1) {
					$pageSize = intval($parts[1]);
				}
			}
		}
		return $pageSize;
	}

	public function resultSize(array $params): int {
		$numberOfRows = -1;
		if (isset($params['size'])) {
			foreach ($params['size'] as $key) {
				$numberOfRows = intval($key);
			}
		}
		return $numberOfRows;
	}

}