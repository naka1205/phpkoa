PHPKoa 
=================


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