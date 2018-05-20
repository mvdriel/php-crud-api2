<?php
namespace Tqd\PhpCrudApi\Controller;

use Tqd\PhpCrudApi\Data\ErrorCode;
use Tqd\PhpCrudApi\Data\Record\ErrorDocument;
use Tqd\PhpCrudApi\Response;

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
