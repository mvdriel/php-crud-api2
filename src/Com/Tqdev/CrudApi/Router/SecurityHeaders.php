<?php
namespace Com\Tqdev\CrudApi\Router;

use Com\Tqdev\CrudApi\Controller\Responder;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;

class SecurityHeaders extends Middleware
{
    private $allowedOrigins;

    public function __construct(Router $router, Responder $responder, String $allowedOrigins)
    {
        $router->load($this);
        $this->allowedOrigins = $allowedOrigins;
    }

    private function isOriginAllowed(String $origin, String $allowedOrigins): bool
    {
        $found = false;
        foreach (explode(',', $allowedOrigins) as $allowedOrigin) {
            $hostname = preg_quote(strtolower(trim($allowedOrigin)));
            $regex = '/^' . str_replace('\*', '.*', $hostname) . '$/';
            if (preg_match($regex, $origin)) {
                $found = true;
                break;
            }
        }
        return $found;
    }

    public function handle(Request $request): Response
    {
        $origin = $request->getHeader('Origin');
        if ($origin) {
            $allowedOrigins = $this->allowedOrigins;
            if (!$this->isOriginAllowed($origin, $allowedOrigins)) {
                return $this->responder->error(ErrorCode::ORIGIN_FORBIDDEN, $origin);
            }
        }
        $method = $request->getMethod();
        if ($method == 'OPTIONS') {
            $response = new Response(Response::OK, '');
            $response->addHeader('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN');
            $response->addHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, PUT, POST, DELETE, PATCH');
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
            $response->addHeader('Access-Control-Max-Age', '1728000');
        } else {
            $response = $this->next->handle($request);
        }
        if ($origin) {
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
            $response->addHeader('Access-Control-Allow-Origin', $origin);
        }
        return $response;
    }
}
