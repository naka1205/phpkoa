<?php
require __DIR__ . '/../vendor/autoload.php';

use Naka507\Koa\Application;
use Naka507\Koa\Context;
use Naka507\Koa\Error;
use Naka507\Koa\Timeout;
use Naka507\Koa\Router;

use GuzzleHttp\Client;

$app = new Application();
$app->υse(new Error());
$app->υse(new Timeout(10));

$router = new Router();

//http://127.0.0.1:5000/curl/get
//http://127.0.0.1:5000/curl/post
//http://127.0.0.1:5000/curl/put
//http://127.0.0.1:5000/curl/delete

$router->mount('/curl', function() use ($router) {
    
    $router->get('/get', function( Context $ctx, $next ) {
        $client = new Client();
        $r = (yield $client->request('GET', 'http://127.0.0.1:3000/demo3/1'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

    $router->get('/post', function(Context $ctx, $next ) {
        $client = new Client();
        $r = (yield $client->request('POST', 'http://127.0.0.1:3000/demo3/2'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

    $router->get('/put', function( Context $ctx, $next ) {
        $client = new Client();
        $r = (yield $client->request('PUT', 'http://127.0.0.1:3000/demo3/3'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

    $router->get('/delete', function( Context $ctx, $next ) {
        $client = new Client();
        $r = (yield $client->request('DELETE', 'http://127.0.0.1:3000/demo3/4'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

});

// $router->get('/curl/(\w+)', function(Context $ctx, $next, $vars) {
//     $method = strtoupper($vars[0]);
//     $client = new Client();
//     $r = (yield $client->request($method, 'http://127.0.0.1:3000/demo3/123'));
//     $ctx->status = $r->getStatusCode();
//     $ctx->body = $r->getBody();
// });

$app->υse($router->routes());

$app->listen(5000);