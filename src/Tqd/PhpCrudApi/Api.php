<?php
namespace Tqd\PhpCrudApi;

use Tqd\PhpCrudApi\Cache\CacheFactory;
use Tqd\PhpCrudApi\Controller\CacheController;
use Tqd\PhpCrudApi\Controller\DataController;
use Tqd\PhpCrudApi\Controller\MetaController;
use Tqd\PhpCrudApi\Controller\OpenApiController;
use Tqd\PhpCrudApi\Controller\Responder;
use Tqd\PhpCrudApi\Database\GenericDB;
use Tqd\PhpCrudApi\Data\DataService;
use Tqd\PhpCrudApi\Data\ErrorCode;
use Tqd\PhpCrudApi\Meta\MetaService;
use Tqd\PhpCrudApi\OpenApi\OpenApiService;
use Tqd\PhpCrudApi\Router\SecurityHeaders;
use Tqd\PhpCrudApi\Router\SimpleRouter;

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
        $router = new SimpleRouter($responder);
        new SecurityHeaders($router, $responder, $config->getAllowedOrigins());
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
                if (strpos(strtolower($e->getMessage()), 'allow nulls') !== false) {
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
