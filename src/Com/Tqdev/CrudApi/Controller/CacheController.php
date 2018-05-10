<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Cache\Cache;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Router\Router;

class CacheController
{
    private $metaService;
    private $apiService;
    private $responder;

    public function __construct(Router $router, Responder $responder, Cache $cache)
    {
        $router->register('GET', '/cache/clear', array($this, 'clear'));
        $this->responder = $responder;
        $this->cache = $cache;
    }

    public function clear(Request $request): Response
    {
        return $this->responder->success($this->cache->clear());
    }

}
