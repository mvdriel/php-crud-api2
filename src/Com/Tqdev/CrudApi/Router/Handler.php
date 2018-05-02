<?php
namespace Com\Tqdev\CrudApi\Router;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;

interface Handler
{
    public function handle(Request $request): Response;
}
