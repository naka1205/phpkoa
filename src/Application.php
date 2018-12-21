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
        $this->context = new Context();
        $this->context->app = $this;
    }

    public function υse(callable $fn)
    {
        $this->middleware[] = $fn;
        return $this;
    }

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
        $reqHandler = $this->makeRequest($ctx);
        $resHandler = $this->makeResponse($ctx);
        spawn($reqHandler, $resHandler);
    }

    protected function makeRequest(Context $ctx)
    {
        return function() use($ctx) {
            yield setCtx("ctx", $ctx);
            $ctx->res->status(404);
            $fn = $this->fn;
            yield $fn($ctx);
        };
    }

    protected function makeResponse(Context $ctx)
    {
        return function($r = null, \Exception $ex = null) use($ctx) {
            if ($ex) {
                $this->error($ctx, $ex);
            } else {
                $this->respond($ctx);
            }
        };
    }

    protected function error(Context $ctx, \Exception $ex = null)
    {
        if ($ex === null) {
            return;
        }

        if ($ex && $ex->getCode() !== 404) {

        }

        $msg = $ex->getCode();
        if ($ex instanceof HttpException) {
            $status = $ex->status ?: 500;
            $ctx->res->status($status);
            $msg = $ex->getMessage();

        } else {
            $ctx->res->status(500);
        }

        $ctx->res->header("Content-Type", "text");
        $ctx->res->write($msg);
        $ctx->res->end();
    }

    protected function respond(Context $ctx)
    {
        if ($ctx->respond === false) return;

        $body = $ctx->body;
        $code = $ctx->status;

        if ($code !== null) {
            $ctx->res->status($code);
        }

        if ($body !== null) {
            $ctx->res->write($body);
        }

        $ctx->res->end();
    }

    protected function createContext( $req, $res)
    {
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