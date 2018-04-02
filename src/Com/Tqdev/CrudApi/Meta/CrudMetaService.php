<?php
namespace Com\Tqdev\CrudApi\Meta;

use Com\Tqdev\CrudApi\Meta\Reflection\DatabaseReflection;

class CrudMetaService {

    public function getDatabaseReflection(): DatabaseReflection {
        return new DatabaseReflection();
    }
}

