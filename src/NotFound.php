<?php
namespace Naka507\Koa;
class NotFound implements Middleware {

    public function __construct()
    {
    }

    public function __invoke(Context $ctx, $next) {
        yield $next;
        if($ctx->status !== 404 || $ctx->body){
            return;
        }
        $ctx->status = 404;
        $ctx->body = (yield Template::render(__DIR__ . "/template/404.html"));
    }
}
