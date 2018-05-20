<?php
namespace Tqd\PhpCrudApi\OpenApi;

use Tqd\PhpCrudApi\Meta\MetaService;

class OpenApiService
{
    private $tables;

    public function __construct(MetaService $meta)
    {
        $this->tables = $meta->getDatabase();
    }

}
