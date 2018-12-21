<?php
require __DIR__ . '/../vendor/autoload.php';

use Naka507\Koa\Application;
use Naka507\Koa\Context;
use Naka507\Koa\Error;
use Naka507\Koa\Timeout;
use Naka507\Koa\Router;
use Naka507\Koa\NotFound;

$app = new Application();

$app->υse(new Error());
$app->υse(new NotFound());
$app->υse(new Timeout(3)); //设置3秒超时

$router = new Router();

$router->get('/hello', function(Context $ctx, $next) {
    $ctx->status = 200;
    $ctx->body = "<h1>Hello World</h1>";
});

//访问超时
$router->get('/timeout', function(Context $ctx, $next) {
    yield async_sleep(5);
});

//访问出错
$router->get('/error', function(Context $ctx, $next) {
    $ctx->thrοw(500, "Internal Error");
    yield;
});

$app->υse($router->routes());

$app->listen(3000);