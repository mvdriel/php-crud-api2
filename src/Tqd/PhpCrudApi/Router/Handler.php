<?php
namespace Tqd\PhpCrudApi\Router;

use Tqd\PhpCrudApi\Request;
use Tqd\PhpCrudApi\Response;

interface Handler
{
    public function handle(Request $request): Response;
}
