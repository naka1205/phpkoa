<?php
namespace Naka507\Koa;
class Error implements Middleware
{
    public function __invoke(Context $ctx, $next)
    {
        try {
            yield $next;
        } catch (\Exception $ex) {
            $status = 500;
            $code = $ex->getCode() ?: 0;
            $msg = "Internal Error";

            // HttpException的异常通常是通过Context的throw方法抛出
            // 状态码与Msg直接提取可用
            if ($ex instanceof HttpException) {
                $status = $ex->status;
                if ($ex->expose) {
                    $msg = $ex->getMessage();
                }
            }
            // 这里可这对其他异常区分处理
            // else if ($ex instanceof otherException) { }

            $err = [ "code" => $code,  "msg" => $msg ];
            if ($ctx->accept("json")) {
                $ctx->status = 200;
                $ctx->body = $err;
            } else {
                $ctx->status = $status;
                if ($status === 404) {
                    $ctx->body = (yield Template::render(__DIR__ . "/404.html"));
                } else if ($status === 500) {
                    $ctx->body = (yield Template::render(__DIR__ . "/500.html", $err));
                } else {
                    $ctx->body = (yield Template::render(__DIR__ . "/error.html", $err));
                }
            }
        }
    }
}