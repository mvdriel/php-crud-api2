<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Api\CrudApiService;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Router\CorsProtectedRouter;

class CrudApiController
{

    private $service;
    private $responder;

    public function __construct(CorsProtectedRouter $router, CrudApiService $service, Responder $responder)
    {
        $router->register('GET', '/data/*', array($this, '_list'));
        $router->register('POST', '/data/*', array($this, 'create'));
        $router->register('GET', '/data/*/*', array($this, 'read'));
        $router->register('PUT', '/data/*/*', array($this, 'update'));
        $router->register('DELETE', '/data/*/*', array($this, 'delete'));
        $this->service = $service;
        $this->responder = $responder;
    }

    public function _list(Request $request): Response
    {
        $table = $request->getPath(2);
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        return $this->responder->success($this->service->_list($table, $params));
    }

    public function create(Request $request): Response
    {
        $table = $request->getPath(2);
        $record = $request->getBody();
        if ($record === null) {
            return $this->responder->error(ErrorCode::HTTP_MESSAGE_NOT_READABLE, '');
        }
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        try {
            return $this->responder->success($this->service->create($table, $record, $params));
        } catch (\PDOException $e) {
            if (strpos(strtolower($e->getMessage()), 'duplicate') !== false) {
                return $this->responder->error(ErrorCode::DUPLICATE_KEY_EXCEPTION, '');
            }
            if (strpos(strtolower($e->getMessage()), 'constraint') !== false) {
                return $this->responder->error(ErrorCode::DATA_INTEGRITY_VIOLATION, '');
            }
            throw $e;
        }
    }

    public function read(Request $request): Response
    {
        $table = $request->getPath(2);
        $id = $request->getPath(3);
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        if (strpos($id, ',') !== false) {
            $ids = explode(',', $id);
            $result = [];
            for ($i = 0; $i < count($ids); $i++) {
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

    public function update(Request $request): Response
    {
        $table = $request->getPath(2);
        $id = $request->getPath(3);
        $record = $request->getBody();
        if ($record === null) {
            return $this->responder->error(ErrorCode::HTTP_MESSAGE_NOT_READABLE, '');
        }
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        return $this->responder->success($this->service->update($table, $id, $record, $params));
    }

    public function delete(Request $request): Response
    {
        $table = $request->getPath(2);
        $id = $request->getPath(3);
        $params = $request->getParams();
        if (!$this->service->exists($table)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $table);
        }
        return $this->responder->success($this->service->delete($table, $id, $params));
    }

}
