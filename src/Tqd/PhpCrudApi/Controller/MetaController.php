<?php
namespace Tqd\PhpCrudApi\Controller;

use Tqd\PhpCrudApi\Data\ErrorCode;
use Tqd\PhpCrudApi\Meta\MetaService;
use Tqd\PhpCrudApi\Request;
use Tqd\PhpCrudApi\Response;
use Tqd\PhpCrudApi\Router\Router;

class MetaController
{
    private $service;
    private $responder;

    public function __construct(Router $router, Responder $responder, MetaService $service)
    {
        $router->register('GET', '/meta', array($this, 'getDatabase'));
        $router->register('GET', '/meta/*', array($this, 'getTable'));
        $router->register('GET', '/meta/*/*', array($this, 'getColumn'));
        $this->service = $service;
        $this->responder = $responder;
    }

    public function getDatabase(Request $request): Response
    {
        $database = $this->service->getDatabase();
        return $this->responder->success($database);
    }

    public function getTable(Request $request): Response
    {
        $tableName = $request->getPathSegment(2);
        if (!$this->service->hasTable($tableName)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $tableName);
        }
        $table = $this->service->getTable($tableName);
        return $this->responder->success($table);
    }

    public function getColumn(Request $request): Response
    {
        $tableName = $request->getPathSegment(2);
        $columnName = $request->getPathSegment(3);
        if (!$this->service->hasTable($tableName)) {
            return $this->responder->error(ErrorCode::TABLE_NOT_FOUND, $tableName);
        }
        $table = $this->service->getTable($tableName);
        if (!$table->exists($columnName)) {
            return $this->responder->error(ErrorCode::COLUMN_NOT_FOUND, $columnName);
        }
        $column = $table->get($columnName);
        return $this->responder->success($column);
    }

}
