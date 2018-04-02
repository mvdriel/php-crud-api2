<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Api\BaseCrudApiService;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;

class CrudApiService extends BaseCrudApiService {

    public function __construct(CrudMetaService $meta) {
        $this->tables = $meta->getDatabaseReflection();
    }

    public function exists(String $table): bool {
        return $this->tables->exists($table);
    }

    public function read(String $table, String $id, array $params): \stdClass {
        return (object)array('bleg'=>'aas');
    }
}