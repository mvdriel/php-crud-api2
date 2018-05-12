<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Data\ErrorCode;
use Com\Tqdev\CrudApi\Meta\MetaService;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Router\Router;

class MetaController
{
    private $service;
    private $responder;

    public function __construct(Router $router, Responder $responder, MetaService $service)
    {
        $router->register('GET', '/meta', array($this, '_list'));
        $router->register('GET', '/meta/*', array($this, 'read'));
        $this->service = $service;
        $this->responder = $responder;
    }

    public function _list(Request $request): Response
    {
        $tables = $this->service->getDatabase();
        return $this->responder->success($tables);
    }

    public function read(Request $request): Response
    {
        $table = $request->getPathSegment(2);
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        $columns = $this->service->get($table);
        return $this->responder->success($columns);
    }

}
