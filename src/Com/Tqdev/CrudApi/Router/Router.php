<?php
namespace Com\Tqdev\CrudApi\Router;

use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Api\PathTree;
use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;

class Router
{

    private $responder;
    private $routes;

    public function __construct(Responder $responder)
    {
        $this->responder = $responder;
        $this->routes = new PathTree();
    }

    public function register(String $method, String $pathGlob, array $handler)
    {
        $path = explode('/', trim($pathGlob, '/'));
        array_unshift($path, $method);
        $this->routes->put($path, $handler);
    }

    public function route(Request $request): Response
    {
        $method = strtoupper($request->getMethod());
        $path = explode('/', trim($request->getPath(0), '/'));
        array_unshift($path, $method);

        $functions = $this->matchPath($path, $this->routes);
        if (count($functions) == 0) {
            return $this->responder->error(ErrorCode::ROUTE_NOT_FOUND, $request->getPath());
        }
        return call_user_func($functions[0], $request);
    }

    private function matchPath(array $path, PathTree $tree): array
    {
        $values = array();
        while (count($path) > 0) {
            $key = array_shift($path);
            if ($tree->has($key)) {
                $tree = $tree->get($key);
            } else if ($tree->has('*')) {
                $tree = $tree->get('*');
            } else {
                $tree = null;
                break;
            }
        }
        if ($tree !== null) {
            $values = $tree->getValues();
        }
        return $values;
    }
}
