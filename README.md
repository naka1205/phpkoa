PHPKoa 
=================
PHP异步编程: 
基于 `PHP` 实(chao)现(xi) `NODEJS` web框架 [KOA](https://github.com/koajs/koa) 

说明
=======
偶然间在 `GITHUB` 上看到有赞官方仓库的 [手把手教你实现co与Koa](https://github.com/youzan/php-co-koa) 。由于此前用过 `KOA` ，对于 `KOA` 的洋葱模型叹为观止。不由得心血来潮的看完了整个文档，接着 `CTRL+C`、`CTRL+V` 让代码跑了起来。
文档中是基于 `swoole` 扩展进行开发，而 `swoole` 对 `WINDOWS` 并不友好，向来习惯在 `WINDOWS` 下开发的我一鼓作气，将[Workerman](https://github.com/walkor/Workerman) 改写并兼容了此项目。

安装
=======
```
composer require naka1205/phpkoa
```

使用
=======

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

$app->listen(3000);

```