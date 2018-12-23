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
$router->get('/hello', function(Context $ctx) {
    $ctx->status = 200;
    $ctx->state["title"] = "HELLO WORLD";
    $ctx->state["time"] = date("Y-m-d H:i:s", time());;
    yield $ctx->render(__DIR__ . "/hello.html");
});

//一维数组
$router->get('/info', function(Context $ctx) {
    $info = array("name" => "小明", "age" => 15);
    $ctx->status = 200;
    $ctx->state["title"] = "这是一个学生信息";
    $ctx->state["info"] = $info;
    yield $ctx->render(__DIR__ . "/info.html");
});
//二维数组
$router->get('/table', function(Context $ctx) {
    $table = array(
        array("name" => "小明", "age" => 15),
        array("name" => "小花", "age" => 13),
        array("name" => "小刚", "age" => 17)
    );
    $ctx->status = 200;
    $ctx->state["title"] = "这是多个学生信息";
    $ctx->state["table"] = $table;
    yield $ctx->render(__DIR__ . "/table.html");
});

$app->υse($router->routes());

$app->listen(3000);