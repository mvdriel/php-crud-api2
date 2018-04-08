<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Api\Record\ErrorDocument;

class Responder {

    public function error(int $error, String $argument): Response {
        $errorCode = new ErrorCode($error);
        $status = $errorCode->getStatus();
        $document = new ErrorDocument($errorCode, $argument);
        return new Response($status, $document);
    }

    public function success($result): Response {
        return new Response(Response::OK, $result);
    }

}