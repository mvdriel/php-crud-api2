<?php
use Com\Tqdev\CrudApi\Api;
use Com\Tqdev\CrudApi\Request;

spl_autoload_register(function ($class_name) {
    include str_replace('\\',DIRECTORY_SEPARATOR,"src\\$class_name.php");
});

$request = new Request();
$request->addHeader('Origin');
$api = new Api('*');
$response = $api->handle($request);
//echo $response;
$response->output();