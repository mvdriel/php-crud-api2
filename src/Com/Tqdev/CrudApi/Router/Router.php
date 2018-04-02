<?php
namespace Com\Tqdev\CrudApi\Router;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Controller\BaseController;

class Router {
    
    protected $handlers;

    public function __construct() {
        $this->handlers = array();
    }

    public function registerListHandler($handler) {
        $this->handlers['list'] = $handler;
    }

    public function registerCreateHandler($handler) {
        $this->handlers['create'] = $handler;
    }

    public function registerReadHandler($handler) {
        $this->handlers['read'] = $handler;
    }

    public function registerUpdateHandler($handler) {
        $this->handlers['update'] = $handler;
    }

    public function registerDeleteHandler($handler) {
        $this->handlers['delete'] = $handler;
    }

    public function registerOpenApiHandler($handler) {
        $this->handlers['openapi'] = $handler;
    }

    public function route(Request $request): Response {
        $method = $request->getMethod();
        $table = $request->getPath(1);
        $id = $request->getPath(2);
        if ($table) {
            switch($method) {
                case 'POST':
                    if (!$id) {
                        $func = 'create';
                    }
                    break;
                case 'GET':
                    if ($id) {
                        $func = 'read';
                    } else {
                        $func = 'list';
                    }
                    break;
                case 'PUT':
                    if ($id) {
                        $func = 'update';
                    }
                    break;
                case 'DELETE':
                    if ($id) {
                        $func = 'delete';
                    }
                    break;
            }
        } else {
            switch($method) {
                case 'GET':
                    $func = 'openapi';
                    break;
            }
        }
        if (!isset($this->handlers[$func])) {
            return BaseController::error(ErrorCode::ROUTE_NOT_FOUND, $request->getPath());
        }
        return call_user_func($this->handlers[$func], $request);
    }
}