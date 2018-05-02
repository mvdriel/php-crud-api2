<?php
namespace Com\Tqdev\CrudApi\Router;

interface Router
{
    public function register(String $method, String $path, array $handler);
}
