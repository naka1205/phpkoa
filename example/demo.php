<?php
require __DIR__ . '/function.php';
require __DIR__ . '/vendor/autoload.php';

// Define OS Type
define('OS_TYPE_LINUX', 'linux');
define('OS_TYPE_WINDOWS', 'windows');


use Koa\Application;
use Koa\Context;

$app = new Application();

$app->υse(function(Context $ctx) {
    $ctx->status = 200;
    $ctx->body = "<h1>Hello World</h1>";
});

$app->listen(3000);