<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;
use Com\Tqdev\CrudApi\Api\ColumnSelector;
use Com\Tqdev\CrudApi\Api\Record\ListResponse;

class CrudApiService {

    protected $db;
    protected $tables;
    protected $columns;

    public function __construct(GenericDB $db, CrudMetaService $meta) {
        $this->db = $db;
        $this->tables = $meta->getDatabaseReflection();
        $this->columns = new ColumnSelector();
    }

	protected function sanitizeRecord(String $tableName, array $record, String $id) {
		$keyset = array_keys((array)$record);
		foreach ($keyset as $key) {
			if (!$this->tables->get($tableName)->exists($key)) {
				unset($record[$key]);
			}
		}
		if ($id != "") {
			$pk = $this->tables->get($tableName)->getPk();
			foreach ($this->tables->get($tableName)->columnNames() as $key) {
				$field = $this->tables->get($tableName)->get($key);
				if ($field->getName() == $pk->getName()) {
					unset($record[$key]);
				}
			}
		}
	}

    public function exists(String $table): bool {
        return $this->tables->exists($table);
    }

	public function create(String $tableName, array $record, array $params) {
		$this->sanitizeRecord($tableName, $record, "");
		$table = $this->tables->get($tableName);
        $columnValues = $this->columns->values($table, true, $record, $params);
        return $this->db->createSingle($table, $columnValues);
	}

    public function read(String $tableName, String $id, array $params)/*: ?\stdClass*/ {
        $table = $this->tables->get($tableName);
        $columnNames = $this->columns->names($table, true, $params);
        return $this->db->selectSingle($table, $columnNames, $id);
    }

    public function update(String $tableName, String $id, array $record, array $params) {
		$this->sanitizeRecord($tableName, $record, $id);
		$table = $this->tables->get($tableName);
        $columnValues = $this->columns->values($table, true, $record, $params);
        return $this->db->updateSingle($table, $columnValues, $id);
    }
    
    public function delete(String $tableName, String $id, array $params) {
		$table = $this->tables->get($tableName);
        return $this->db->deleteSingle($table, $id);
	}

    public function list(String $tableName, array $params): ListResponse {
        $table = $this->tables->get($tableName);
        $columnNames = $this->columns->names($table, true, $params);
        $count = 0;
        //if ($pagination) {
        //$records =
        //$count = 
        //} else {
        $records = $this->db->selectAll($table, $columnNames);
        return new ListResponse($records, $count);
    }
}