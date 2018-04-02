<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Api\Record\ErrorDocument;
use Com\Tqdev\CrudApi\Controller\BaseController;

class CrudApiController extends BaseController {
    
    protected $service;

    public function __construct($router, $service) {
        $this->service = $service;
        $router->registerListHandler(array($this,'list'));
        $router->registerCreateHandler(array($this,'create'));
        $router->registerReadHandler(array($this,'read'));
        $router->registerUpdateHandler(array($this,'update'));
        $router->registerDeleteHandler(array($this,'delete'));
    }

    public function list(Request $request): Response {
        $table = $request->getPath(1);
		$params = $request->getParams();
		if (!$this->service->exists($table)) {
			return $this->error(ErrorCode::TABLE_NOT_FOUND, $table);
		}
		return $this->success($this->service->list($table, $params));
    }

    public function create(Request $request): Response {
        
    }

    public function read(Request $request): Response {
        $table = $request->getPath(1);
		$id = $request->getPath(2);
		$params = $request->getParams();
		if (!$this->service->exists($table)) {
			return $this->error(ErrorCode::TABLE_NOT_FOUND, $table);
		}
		return $this->success($this->service->read($table, $id, $params));
    }

    public function update(Request $request): Response {
        
    }

    public function delete(Request $request): Response {
        
    }

}