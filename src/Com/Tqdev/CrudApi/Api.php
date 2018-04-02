<?php
namespace Com\Tqdev\CrudApi;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Controller\BaseController;
use Com\Tqdev\CrudApi\Controller\CrudApiController;
use Com\Tqdev\CrudApi\Router\CorsProtectedRouter;
use Com\Tqdev\CrudApi\Api\CrudApiService;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;

class Api {
    
    protected $router;

    public function __construct(String $allowedOrigins) {
        $meta = new CrudMetaService();
        $router = new CorsProtectedRouter($allowedOrigins);
        $api = new CrudApiService($meta);
        new CrudApiController($router,$api);
        $this->router = $router;
    }

    public function handle(Request $request): Response {
        $response = null;
        try {
            $response = $this->router->route($request);
        } catch (\Throwable $e) {
            $response = BaseController::error(ErrorCode::ERROR_NOT_FOUND, $e->getMessage());
        }
        return $response;
    }
}