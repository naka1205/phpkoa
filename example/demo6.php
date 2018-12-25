<?php
require __DIR__ . '/../vendor/autoload.php';

define('DS', DIRECTORY_SEPARATOR);

use Naka507\Koa\Application;
use Naka507\Koa\Context;
use Naka507\Koa\Error;
use Naka507\Koa\Timeout;
use Naka507\Koa\NotFound;
use Naka507\Koa\Router;
use Naka507\Koa\StaticFiles; 

$app = new Application();
$app->υse(new Error());
$app->υse(new Timeout(5));
$app->υse(new NotFound()); 

$public_path = __DIR__ . DS .  "public" ;
$app->υse(new StaticFiles( $public_path )); 

$router = new Router();

$router->get('/index', function(Context $ctx, $next) {
    $ctx->status = 200;
    yield $ctx->render(__DIR__ . "/template/index.html");
});

//文件上传
$router->post('/files', function(Context $ctx, $next) use ($public_path) {
    $upload_path = "uploads";
    $save_path = $public_path . DS .  $upload_path . DS;

    if ( !is_dir($save_path) ) {
        mkdir ($save_path , 0777, true);
    }
    $files = [];
    foreach ( $ctx->request->files as $key => $value) {
        if ( !$value['file_name'] || !$value['file_data'] ) {
            continue;
        }
        $ext = explode(".",$value['file_name'])[1]; 
        $file_name = time() . "." . $ext;
        $file_path = $save_path . $file_name;
        file_put_contents($file_path, $value['file_data']);
        $value['file_path'] = "/" . $upload_path . "/" . $file_name ;
        unset($value['file_data']);
        $files[] = $value;
    }
    $ctx->status = 200;
    $ctx->body = json_encode($files);
});

$app->υse($router->routes());

$app->listen(3000);