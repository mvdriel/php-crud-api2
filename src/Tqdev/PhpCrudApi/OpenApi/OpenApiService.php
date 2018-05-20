<?php
namespace Tqdev\PhpCrudApi\OpenApi;

use Tqdev\PhpCrudApi\Meta\MetaService;

class OpenApiService
{
    private $tables;

    public function __construct(MetaService $meta)
    {
        $this->tables = $meta->getDatabase();
    }

}
