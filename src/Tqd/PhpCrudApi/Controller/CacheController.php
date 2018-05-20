<?php
namespace Tqd\PhpCrudApi\Controller;

use Tqd\PhpCrudApi\Cache\Cache;
use Tqd\PhpCrudApi\Request;
use Tqd\PhpCrudApi\Response;
use Tqd\PhpCrudApi\Router\Router;

class CacheController
{
    private $cache;
    private $responder;

    public function __construct(Router $router, Responder $responder, Cache $cache)
    {
        $router->register('GET', '/cache/clear', array($this, 'clear'));
        $this->cache = $cache;
        $this->responder = $responder;
    }

    public function clear(Request $request): Response
    {
        return $this->responder->success($this->cache->clear());
    }

}
