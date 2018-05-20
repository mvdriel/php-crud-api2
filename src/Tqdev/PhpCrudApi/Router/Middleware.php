<?php
namespace Tqdev\PhpCrudApi\Router;

abstract class Middleware implements Handler
{
    protected $next;

    public function setNext(Handler $handler) /*: void*/
    {
        $this->next = $handler;
    }
}
