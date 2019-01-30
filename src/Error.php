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

            if ( $ex instanceof HttpException ) {
                $status = $ex->getStatus();
                $msg = $ex->getMessage();
            }

            $err = [ "code" => $code,  "msg" => $msg ];
            if ( $ctx->accept("json") ) {
                $ctx->status = 200;
                $ctx->body = json_encode($err);
            } else {
                $ctx->status = $status;
                if ($status === 404) {
                    $ctx->body = (yield Template::render(__DIR__ . "/template/404.html"));
                } else if ($status === 500) {
                    $ctx->body = (yield Template::render(__DIR__ . "/template/500.html"));
                } else {
                    $ctx->body = (yield Template::render(__DIR__ . "/template/error.html",$err));
                }
            }

            
        }
    }
}