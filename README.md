PHPKoa 
=================
[![Latest Stable Version](https://poser.pugx.org/naka1205/phpkoa/version)](https://packagist.org/packages/naka1205/phpkoa)
[![Total Downloads](https://poser.pugx.org/naka1205/phpkoa/downloads)](https://packagist.org/packages/naka1205/phpkoa)
[![License](https://poser.pugx.org/naka1205/phpkoa/license)](https://packagist.org/packages/naka1205/phpkoa)

PHP异步编程: 
基于 `PHP` 实(chao)现(xi) `NODEJS` web框架 [KOA](https://github.com/koajs/koa) 

说明
=======
偶然间在 `GITHUB` 上看到有赞官方仓库的 [手把手教你实现co与Koa](https://github.com/youzan/php-co-koa) 。由于此前用过 `KOA` ，对于 `KOA` 的洋葱模型叹为观止。不由得心血来潮的看完了整个文档，接着 `CTRL+C`、`CTRL+V` 让代码跑了起来。
文档中是基于 `swoole` 扩展进行开发，而 `swoole` 对 `WINDOWS` 并不友好，向来习惯在 `WINDOWS` 下开发的我一鼓作气，将[Workerman](https://github.com/walkor/Workerman) 改写并兼容了此项目。

体验
=======
1. [PHPKoa Demo](https://github.com/naka1205/phpkoa_demo) 是使用 `PHPKoa` 开发 `HTTP SERVER` 的一个简单示例！
2. [PHP Krpano](https://github.com/naka1205/phpkrpano) PHP 全景图片生成！

安装
=======
```
composer require naka1205/phpkoa
```

使用
=======

### Hello World
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Naka507\Koa\Application;
use Naka507\Koa\Context;

$app = new Application();

$app->υse(function(Context $ctx) {
    $ctx->status = 200;
    $ctx->body = "<h1>Hello World</h1>";
});

$app->listen(3000,function(){
    echo "PHPKoa is listening in 3000\n";
});

```
### Error
```php
<?php
require __DIR__ . '/vendor/autoload.php';

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

//正常访问
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

```
### Router
```php
<?php
require __DIR__ . '/vendor/autoload.php';

define('DS', DIRECTORY_SEPARATOR);

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
    $ctx->body = "demo1";
});
$router->get('/demo2', function(Context $ctx, $next) {
    $ctx->body = "demo2";
});
$router->get('/demo3/(\d+)', function(Context $ctx, $next, $vars) {
    $ctx->status = 200;
    $ctx->body = "demo3={$vars[0]}";
});
$router->get('/demo4', function(Context $ctx, $next) {
    $ctx->redirect("/demo2");
});

//RESTful API
$router->post('/demo3/(\d+)', function(Context $ctx, $next, $vars) {
    //设置 session
    $ctx->setSession('demo3',$vars[0]);
    //设置 cookie
    $ctx->setCookie('demo3',$vars[0]);
    $ctx->status = 200;
    $ctx->body = "post:demo3={$vars[0]}";
});
$router->put('/demo3/(\d+)', function(Context $ctx, $next, $vars) {

    //获取单个 cookie
    $cookie_demo3 = $ctx->getCookie('demo3');
    //或者
    $cookies = $ctx->cookies['demo3'];

    //获取单个 session
    $session_demo3 = $ctx->getSession('demo3');
    //或者
    $session = $ctx->session['demo3'];

    $ctx->status = 200;
    $ctx->body = "put:demo3={$vars[0]}";
});
$router->delete('/demo3/(\d+)', function(Context $ctx, $next, $vars) {
    //清除所有 cookie
    $ctx->clearCookie();
    //清除所有 session
    $ctx->clearSession();
    $ctx->status = 200;
    $ctx->body = "delete:demo3={$vars[0]}";
});

//文件上传
$router->post('/files/(\d+)', function(Context $ctx, $next, $vars) {
    $upload_path = __DIR__ . DS .  "uploads" . DS;
    if ( !is_dir($upload_path) ) {
        mkdir ($upload_path , 0777, true);
    }
    $files = [];
    foreach ( $ctx->request->files as $key => $value) {
        if ( !$value['file_name'] || !$value['file_data'] ) {
            continue;
        }
        $file_path = $upload_path . $value['file_name'];
        file_put_contents($file_path, $value['file_data']);
        $value['file_path'] = $file_path;
        $files[] = $value;
    }

    $ctx->status = 200;
    $ctx->body = json_encode($files);
});


$app->υse($router->routes());

$app->listen(3000);

```

```php
<?php
//此处已省略 ... 

//使用第三方 HTTP 客户端类库，方便测试
use GuzzleHttp\Client;

$router = new Router();

//路由分组 
//http://127.0.0.1:5000/curl/get
//http://127.0.0.1:5000/curl/post
//http://127.0.0.1:5000/curl/put
//http://127.0.0.1:5000/curl/delete
$router->mount('/curl', function() use ($router) {
    $client = new Client();
    $router->get('/get', function( Context $ctx, $next ) use ($client) {
        $r = (yield $client->request('GET', 'http://127.0.0.1:3000/demo3/1'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

    $router->get('/post', function(Context $ctx, $next ) use ($client){
        $r = (yield $client->request('POST', 'http://127.0.0.1:3000/demo3/2'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

    $router->get('/put', function( Context $ctx, $next ) use ($client){
        $r = (yield $client->request('PUT', 'http://127.0.0.1:3000/demo3/3'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });

    $router->get('/delete', function( Context $ctx, $next ) use ($client){
        $r = (yield $client->request('DELETE', 'http://127.0.0.1:3000/demo3/4'));
        $ctx->status = $r->getStatusCode();
        $ctx->body = $r->getBody();
    });
});

//http://127.0.0.1:5000/files
$router->get('/files', function(Context $ctx, $next ) {
    $client = new Client();
    $r = ( yield $client->request('POST', 'http://127.0.0.1:3000/files/2', [
        'multipart' => [
            [
                'name'     => 'file_name',
                'contents' => fopen( __DIR__ . '/file.txt', 'r')
            ],
            [
                'name'     => 'other_file',
                'contents' => 'hello',
                'filename' => 'filename.txt',
                'headers'  => [
                    'X-Foo' => 'this is an extra header to include'
                ]
            ]
        ]
    ]));
    
    $ctx->status = $r->getStatusCode();
    $ctx->body = $r->getBody();
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
```

### Template

```html
<body>
    <h1>{title}</h1>
    <p>{time}</p>
</body>
```
```php
<?php
require __DIR__ . '/vendor/autoload.php';

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
$app->υse($router->routes());

$app->listen(3000);

```
```html
<body>
    <p>{title}</p>
    <table border=1>
      <tr><td>Name</td><td>Age</td></tr>
  
      <!-- BEGIN INFO -->
      <tr>
        <td> {name} </td>
        <td> {age} </td>
      </tr>
      <!-- END INFO -->
  
    </table>
</body>
```
```php
<?php
//此处已省略 ... 

//一维数组
$router->get('/info', function(Context $ctx) {
    $info = array("name" => "小明", "age" => 15);
    $ctx->status = 200;
    $ctx->state["title"] = "这是一个学生信息";
    $ctx->state["info"] = $info;
    yield $ctx->render(__DIR__ . "/info.html");
});
```
```html
<body>
    <p>{title}</p>
    <table border=1>
      <tr><td>Name</td><td>Age</td></tr>
  
      <!-- BEGIN TABLE -->
      <tr>
        <td> {name} </td>
        <td> {age} </td>
      </tr>
      <!-- END TABLE -->
  
    </table>
</body>
```
```php
<?php
//此处已省略 ... 

//二维数组
$router->get('/table', function(Context $ctx) {
    $table = array(
        array("name" => "小明", "age" => 15),
        array("name" => "小花", "age" => 13),
        array("name" => "小刚", "age" => 17)
    );
    $ctx->status = 200;
    $ctx->state["title"] = "这是一个学生名单";
    $ctx->state["table"] = $table;
    yield $ctx->render(__DIR__ . "/table.html");
});
```

中间件
=======
静态文件处理 中间件 [PHPKoa Static](https://github.com/naka1205/phpkoa_static)

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHPkoa Static</title>
    <link rel="stylesheet" href="/css/default.css">
</head>
<body>
    <img src="/images/20264902.jpg" />
</body>
</html>
```
```php
<?php
require __DIR__ . '/vendor/autoload.php';

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

use Naka507\Koa\Application;
use Naka507\Koa\Context;
use Naka507\Koa\Error;
use Naka507\Koa\Timeout;
use Naka507\Koa\NotFound;
use Naka507\Koa\Router;
//静态文件处理 中间件
use Naka507\Koa\StaticFiles; 

$app = new Application();
$app->υse(new Error());
$app->υse(new Timeout(5));
$app->υse(new NotFound()); 
$app->υse(new StaticFiles(__DIR__ . DS .  "static" )); 

$router = new Router();

$router->get('/index', function(Context $ctx, $next) {
    $ctx->status = 200;
    yield $ctx->render(__DIR__ . "/index.html");
});

$app->υse($router->routes());

$app->listen(3000);
```
