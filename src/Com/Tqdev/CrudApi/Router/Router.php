<?php
namespace Com\Tqdev\CrudApi\Router;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;

interface Router extends Handler
{
    public function register(String $method, String $path, array $handler);

    public function route(Request $request): Response;
}
