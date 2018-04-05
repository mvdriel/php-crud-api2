<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Response;

class ErrorCode {

    protected $code;
    protected $message;
    protected $status;

    const ERROR_NOT_FOUND = 9999;
    const ROUTE_NOT_FOUND = 1000;
    const TABLE_NOT_FOUND = 1001;
    const ARGUMENT_COUNT_MISMATCH = 1002;
    const RECORD_NOT_FOUND = 1003;
    const ORIGIN_FORBIDDEN = 1004;
    const HTTP_MESSAGE_NOT_READABLE = 1008;
    const DUPLICATE_KEY_EXCEPTION = 1009;
    const DATA_INTEGRITY_VIOLATION = 1010;

    protected $values = [
        9999 => ["%s", Response::INTERNAL_SERVER_ERROR],
        1000 => ["Route '%s' not found", Response::NOT_FOUND],
        1001 => ["Table '%s' not found", Response::NOT_FOUND],
        1002 => ["Argument count mismatch in '%s'", Response::NOT_ACCEPTABLE],
        1003 => ["Record '%s' not found", Response::NOT_FOUND],
        1004 => ["Origin '%s' is forbidden", Response::FORBIDDEN],
        1008 => ["Cannot read HTTP message", Response::NOT_ACCEPTABLE],
        1009 => ["Duplicate key exception", Response::NOT_ACCEPTABLE],
        1010 => ["Data integrity violation", Response::NOT_ACCEPTABLE],
    ];

    public function __construct(int $code) {
        if (!isset($this->values[$code])) {
            $code = 9999;
        }
        $this->code = $code;
        $this->message = $this->values[$code][0];
        $this->status = $this->values[$code][1];
    }

	public function getCode(): int {
		return $this->code;
	}

    public function getMessage(String $argument): String {
		return sprintf($this->message, $argument);
	}

	public function getStatus(): int {
		return $this->status;
	}
    
}