<?php
namespace Com\Tqdev\CrudApi;

use Com\Tqdev\CrudApi\Api\CrudApiService;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Controller\CrudApiController;
use Com\Tqdev\CrudApi\Controller\CrudMetaController;
use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;
use Com\Tqdev\CrudApi\Router\CorsMiddleware;
use Com\Tqdev\CrudApi\Router\GlobRouter;

class Api
{
    private $router;
    private $responder;
    private $debug;

    public function __construct(Config $config)
    {
        $db = new GenericDB(
            $config->getDriver(),
            $config->getAddress(),
            $config->getPort(),
            $config->getDatabase(),
            $config->getUsername(),
            $config->getPassword()
        );
        $meta = new CrudMetaService($db);
        $responder = new Responder();
        $router = new GlobRouter($responder);
        new CorsMiddleware($router, $responder, $config->getAllowedOrigins());
        $api = new CrudApiService($db, $meta);
        new CrudApiController($router, $responder, $api);
        new CrudMetaController($router, $responder, $meta, $api);
        $this->router = $router;
        $this->responder = $responder;
        $this->debug = $config->getDebug();
    }

    public function handle(Request $request): Response
    {
        $response = null;
        try {
            $response = $this->router->route($request);
        } catch (\Throwable $e) {
            if ($e instanceof \PDOException) {
                if (strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
                    return $this->responder->error(ErrorCode::DUPLICATE_KEY_EXCEPTION, '');
                }
                if (strpos(strtolower($e->getMessage()), 'constraint') !== false) {
                    return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
                }
            }
            $response = $this->responder->error(ErrorCode::ERROR_NOT_FOUND, $e->getMessage());
            if ($this->debug) {
                $response->addHeader('X-Debug-Info', 'Exception in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        }
        return $response;
    }
}
