<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Api\CrudApiService;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Api\Record\ErrorDocument;
use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Router\CorsProtectedRouter;

class CrudApiController {
    
    protected $service;
    protected $responder;

    public function __construct(CorsProtectedRouter $router, CrudApiService $service, Responder $responder) {
        $router->registerListHandler(array($this,'list'));
        $router->registerCreateHandler(array($this,'create'));
        $router->registerReadHandler(array($this,'read'));
        $router->registerUpdateHandler(array($this,'update'));
        $router->registerDeleteHandler(array($this,'delete'));
        $this->service = $service;
        $this->responder = $responder;
    }

    public function list(Request $request): Response {
        $table = $request->getPath(1);
		$params = $request->getParams();
		if (!$this->service->exists($table)) {
			return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
		}
		return $this->responder->success($this->service->list($table, $params));
    }

    public function create(Request $request): Response {
        
    }

    public function read(Request $request): Response {
        $table = $request->getPath(1);
		$id = $request->getPath(2);
		$params = $request->getParams();
		if (!$this->service->exists($table)) {
			return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        if (strpos($id, ',')!==false) {
            $ids = explode(',', $id);
            $result = [];
            for ($i=0; $i<count($ids); $i++) {
                array_push($result, $this->service->read($table, $ids[$i], $params));
            }
            return $this->responder->success($result);
        } else {
            $response = $this->service->read($table, $id, $params);
            if ($response === null) {
                return $this->responder->error(ErrorCode::RECORD_NOT_FOUND, $id);
            }
            return $this->responder->success($response);
        }		
    }

    public function update(Request $request): Response {
        
    }

    public function delete(Request $request): Response {
        
    }

}