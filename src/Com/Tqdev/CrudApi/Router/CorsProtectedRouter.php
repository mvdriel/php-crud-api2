<?php
namespace Com\Tqdev\CrudApi\Router;

use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Router\Router;

class CorsProtectedRouter extends Router {
    
    protected $allowedOrigins;

    public function __construct(String $allowedOrigins) {
        $this->allowedOrigins = $allowedOrigins;
        parent::__construct();
    }

    protected function isOriginAllowed(String $origin, String $allowedOrigins): boolean {
        $found = false;
        foreach (explode(',',$allowedOrigins) as $allowedOrigin) {
            $hostname = preg_quote(strtolower(trim($allowedOrigin)));
            $regex = '/^'.str_replace('\*','.*',$hostname).'$/';
            if (preg_match($regex, $origin)) { 
                $found = true;
    			break;
            }
        }
        return $found;
    }

    public function route(Request $request): Response {
        $origin = $request->getHeader('Origin');
        if ($origin) {
            $allowedOrigins = $this->allowedOrigins;
            if (!$this->isOriginAllowed($origin, $allowedOrigins)) {
                return BaseController::error(ErrorCode::ORIGIN_FORBIDDEN, $origin);
            }
        }
        $method = $request->getMethod();
        if ($method == 'OPTIONS') {
            $response = new Response();
            $response->addHeader('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN');
            $response->addHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, PUT, POST, DELETE, PATCH');
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
            $response->addHeader('Access-Control-Max-Age', '1728000');
        } else {
            $response = parent::route($request);
        }
        if ($origin) {
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
            $response->addHeader('Access-Control-Allow-Origin', $origin);
        }
        return $response;
    }
}