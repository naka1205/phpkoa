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

$router->get('/get/(\w+)', function(Context $ctx, $next, $vars) {
    $name = '';
    if ( $vars[0] == 'cookies' ) {
        $name = $ctx->getCookie('name1');
    }else{
        $name = $ctx->getSession('name1');
    }
    $ctx->status = 200;
    $ctx->body = $name;
});

$router->get('/set/(\d+)', function(Context $ctx, $next, $vars) {
    $ctx->setSession('name1',$vars[0]);
    $ctx->setCookie('name1',$vars[0]);
    $ctx->status = 200;
    $ctx->body = $vars[0];
});

$router->get('/clear', function(Context $ctx, $next) {
    $ctx->clearCookie();
    $ctx->clearCSession();
    $ctx->status = 200;
    $ctx->body = 'OK';
});

$router->get('/session', function(Context $ctx, $next) {
    $session = $ctx->session;
    $ctx->status = 200;
    $ctx->body = json_encode($session);
});
$router->get('/cookies', function(Context $ctx, $next) {
    $cookies = $ctx->cookies;
    $ctx->status = 200;
    $ctx->body = json_encode($cookies);
});

$app->υse($router->routes());

$app->listen(3000);