<?php
namespace Com\Tqdev\CrudApi;

use Com\Tqdev\CrudApi\Cache\CacheFactory;
use Com\Tqdev\CrudApi\Controller\CacheController;
use Com\Tqdev\CrudApi\Controller\DataController;
use Com\Tqdev\CrudApi\Controller\MetaController;
use Com\Tqdev\CrudApi\Controller\OpenApiController;
use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Data\DataService;
use Com\Tqdev\CrudApi\Data\ErrorCode;
use Com\Tqdev\CrudApi\Meta\MetaService;
use Com\Tqdev\CrudApi\OpenApi\OpenApiService;
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
        $cache = CacheFactory::create($config);
        $meta = new MetaService($db, $cache, $config->getCacheTime());
        $responder = new Responder();
        $router = new GlobRouter($responder);
        new CorsMiddleware($router, $responder, $config->getAllowedOrigins());
        $data = new DataService($db, $meta);
        $openApi = new OpenApiService($meta);
        new DataController($router, $responder, $data);
        new MetaController($router, $responder, $meta);
        new CacheController($router, $responder, $cache);
        new OpenApiController($router, $responder, $openApi);
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
                if (strpos(strtolower($e->getMessage()), 'default value') !== false) {
                    return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
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
