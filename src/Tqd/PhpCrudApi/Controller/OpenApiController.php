<?php
namespace Tqd\PhpCrudApi\Controller;

use Tqd\PhpCrudApi\OpenApi\OpenApiService;
use Tqd\PhpCrudApi\Request;
use Tqd\PhpCrudApi\Response;
use Tqd\PhpCrudApi\Router\Router;

class OpenApiController
{
    private $openApi;
    private $responder;

    public function __construct(Router $router, Responder $responder, OpenApiService $openApi)
    {
        $router->register('GET', '/openapi', array($this, 'openapi'));
        $this->openApi = $openApi;
        $this->responder = $responder;
    }

    public function openapi(Request $request): Response
    {
        return $this->responder->success(false);
    }

}
