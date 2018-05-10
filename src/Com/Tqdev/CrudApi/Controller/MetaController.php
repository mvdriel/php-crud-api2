<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Meta\MetaService;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Router\Router;

class MetaController
{
    private $metaService;
    private $responder;

    public function __construct(Router $router, Responder $responder, MetaService $metaService)
    {
        $router->register('GET', '/meta/columns', array($this, 'columns'));
        $router->register('GET', '/meta/openapi', array($this, 'openapi'));
        $this->metaService = $metaService;
        $this->responder = $responder;
    }

    public function columns(Request $request): Response
    {
        return $this->responder->success($this->metaService->getDatabaseReflection());
    }

    public function openapi(Request $request): Response
    {
        return $this->responder->success($this->metaService->getOpenApiDefinition());
    }

}
