<?php
namespace Naka507\Koa;
use Naka507\Socket\Server;

class Application
{
    public $httpServer;
    public $context;
    public $middleware = [];
    public $fn;

    public function __construct()
    {
        // 我们构造一个Context原型
        $this->context = new Context();
        $this->context->app = $this;
    }

    // 我们用υse方法添加符合接口的中间件
    // middleware :: (Context $ctx, $next) -> void
    public function υse(callable $fn)
    {
        $this->middleware[] = $fn;
        return $this;
    }

    // compose中间件 监听端口提供服务
    public function listen($port = 8000)
    {
        $this->fn = compose($this->middleware);
        $this->httpServer = new Server($port);
        
        $this->httpServer->onConnect = [$this,'onConnect'];
        $this->httpServer->onMessage = [$this,'onRequest'];
        $this->httpServer->start();
        
    }

    public function onConnect(){

    }

    public function onRequest(  $req, $res)
    {
        $ctx = $this->createContext($req, $res);
        $reqHandler = $this->makeRequestHandler($ctx);
        $resHandler = $this->makeResponseHandler($ctx);
        spawn($reqHandler, $resHandler);
    }

    protected function makeRequestHandler(Context $ctx)
    {
        return function() use($ctx) {
            yield setCtx("ctx", $ctx);
            $ctx->res->status(404);
            $fn = $this->fn;
            yield $fn($ctx);
        };
    }

    protected function makeResponseHandler(Context $ctx)
    {
        return function($r = null, \Exception $ex = null) use($ctx) {
            if ($ex) {
                $this->handleError($ctx, $ex);
            } else {
                $this->respond($ctx);
            }
        };
    }

    protected function handleError(Context $ctx, \Exception $ex = null)
    {
        if ($ex === null) {
            return;
        }

        if ($ex && $ex->getCode() !== 404) {
            sys_error($ctx);
            sys_error($ex);
        }

        // 非 Http异常， 统一500 status，对外显示异常code
        // Http 异常，自定义status，自定义是否暴露Msg
        $msg = $ex->getCode();
        if ($ex instanceof HttpException) {
            $status = $ex->status ?: 500;
            $ctx->res->status($status);
            if ($ex->expose) {
                $msg = $ex->getMessage();
            }
        } else {
            $ctx->res->status(500);
        }

        // force text/plain
        $ctx->res->header("Content-Type", "text"); // TODO accepts
        $ctx->res->write($msg);
        $ctx->res->end();
    }

    protected function respond(Context $ctx)
    {
        if ($ctx->respond === false) return; // allow bypassing Koa

        $body = $ctx->body;
        $code = $ctx->status;

        if ($code !== null) {
            $ctx->res->status($code);
        }
        // status.empty() $ctx->body = null; res->end()

        if ($body !== null) {
            $ctx->res->write($body);
        }

        $ctx->res->end();
    }

    protected function createContext( $req, $res)
    {
        // 可以在Context挂其他组件 $app->foo = bar; $app->listen();
        $context = clone $this->context;

        $request = $context->request = new Request($this, $context, $req, $res);
        $response = $context->response = new Response($this, $context, $req, $res);

        $context->app = $this;
        $context->req = $req;
        $context->res = $res;

        $request->response = $response;
        $response->request = $request;

        $request->originalUrl = $req->server["request_uri"];
        $request->ip = $req->server["remote_addr"];

        return $context;
    }
}