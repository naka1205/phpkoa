<?php
require __DIR__ . '/../vendor/autoload.php';

use Naka507\Koa\Application;
use Naka507\Koa\Context;
use Naka507\Koa\Error;
use Naka507\Koa\Timeout;
use Naka507\Koa\Router;

$app = new Application();
$app->υse(new Error());
$app->υse(new Timeout(5));

$router = new Router();
$router->get('/demo1', function(Context $ctx, $next) {
    $ctx->status = 200;
    $ctx->body = "demo1";
});
$router->get('/demo2', function(Context $ctx, $next) {
    $ctx->status = 200;
    $ctx->body = "demo2";
});
$router->get('/demo3/(\d+)', function(Context $ctx, $next, $vars) {
    $ctx->status = 200;
    $ctx->body = "demo3={$vars[0]}";
});
$app->υse($router->routes());

$app->listen(3000);