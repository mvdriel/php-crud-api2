<?php
namespace Com\Tqdev\CrudApi\OpenApi;

use Com\Tqdev\CrudApi\Meta\MetaService;

class OpenApiService
{
    private $tables;

    public function __construct(MetaService $meta)
    {
        $this->tables = $meta->getDatabase();
    }

}
