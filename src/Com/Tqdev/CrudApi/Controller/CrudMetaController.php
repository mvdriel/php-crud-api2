<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Api\CrudApiService;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Router\Router;

class CrudMetaController
{
    private $metaService;
    private $apiService;
    private $responder;

    public function __construct(Router $router, Responder $responder, CrudMetaService $metaService, CrudApiService $apiService)
    {
        $router->register('GET', '/meta/columns', array($this, 'columns'));
        $router->register('GET', '/meta/records', array($this, 'records'));
        $router->register('GET', '/meta/openapi', array($this, 'openapi'));
        $this->metaService = $metaService;
        $this->apiService = $apiService;
        $this->responder = $responder;
    }

    public function columns(Request $request): Response
    {
        return $this->responder->success($this->metaService->getDatabaseReflection());
    }

    public function records(Request $request): Response
    {
        return $this->responder->success($this->apiService->getDatabaseRecords());
    }

    public function openapi(Request $request): Response
    {
        return $this->responder->success($this->metaService->getOpenApiDefinition());
    }

}
