<?php
use Com\Tqdev\CrudApi\Api;
use Com\Tqdev\CrudApi\Config;
use Com\Tqdev\CrudApi\Request;

// do not reformat the following line
spl_autoload_register(function ($class) {include str_replace('\\', DIRECTORY_SEPARATOR, "src\\$class.php");});
// as it is excluded in the build

$config = new Config([
    'database' => 'php-crud-api',
    'username' => 'php-crud-api',
    'password' => 'php-crud-api',
//    'debug' => true,
]);
$request = new Request('GET', '/data/posts/1');
$request->addHeader('Origin');
$api = new Api($config);
$response = $api->handle($request);
//echo $response;
$response->output();
