<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Api\BaseCrudApiService;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;

class CrudApiService extends BaseCrudApiService {

    protected $db;

    public function __construct(GenericDB $db, CrudMetaService $meta) {
        $this->db = $db;
        $this->tables = $meta->getDatabaseReflection();
    }

    public function exists(String $table): bool {
        return $this->tables->exists($table);
    }

    public function read(String $table, String $id, array $params): \stdClass {
        return (object)$this->db->selectSingle(['id','content'],$table,'id',$id);
    }

    public function list(String $table, array $params): array {
        return $this->db->selectAll(['id','content'],$table);
    }
}