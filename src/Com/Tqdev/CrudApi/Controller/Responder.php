<?php
namespace Com\Tqdev\CrudApi\Controller;

use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Api\Record\ErrorDocument;
use Com\Tqdev\CrudApi\Response;

class Responder
{

    public function error(int $error, String $argument): Response
    {
        $errorCode = new ErrorCode($error);
        $status = $errorCode->getStatus();
        $document = new ErrorDocument($errorCode, $argument);
        return new Response($status, $document);
    }

    public function success($result): Response
    {
        return new Response(Response::OK, $result);
    }

}
