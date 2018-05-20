<?php
namespace Tqd\PhpCrudApi\Router;

use Tqd\PhpCrudApi\Request;
use Tqd\PhpCrudApi\Response;

interface Router extends Handler
{
    public function register(String $method, String $path, array $handler);

    public function load(Middleware $middleware);

    public function route(Request $request): Response;
}
