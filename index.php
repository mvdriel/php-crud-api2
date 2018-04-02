<?php
use Com\Tqdev\CrudApi\Api;
use Com\Tqdev\CrudApi\Request;

// do not reformat the following line
spl_autoload_register(function ($class) { include str_replace('\\',DIRECTORY_SEPARATOR,"src\\$class.php"); });
// as it is excluded in the build

$request = new Request();
$request->addHeader('Origin');
$api = new Api('*');
$response = $api->handle($request);
//echo $response;
$response->output();