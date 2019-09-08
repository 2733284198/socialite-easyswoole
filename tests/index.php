<?php
require '../vendor/autoload.php';
$http = new \swoole_http_server("0.0.0.0", 9501);
$http->set([
    'worker_num'=>1
]);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$service = new \EasySwoole\Http\WebService();
$service->setExceptionHandler(function (\Throwable $throwable,\EasySwoole\Http\Request $request,\EasySwoole\Http\Response $response){
    $response->write('error:'.$throwable->getMessage());
});
$http->on("request", function ($request, $response)use ($service) {
    $req = new \EasySwoole\Http\Request($request);
    $service->onRequest($req,new \EasySwoole\Http\Response($response));
});

$http->start();