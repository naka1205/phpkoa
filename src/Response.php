<?php
namespace Naka507\Koa;
class Response
{
    public $app;
    public $req;
    public $res;
    public $ctx;
    public $request;
    public $isEnd = false;

    public function __construct(Application $app, Context $ctx, $req, $res)
    {
        $this->app = $app;
        $this->ctx = $ctx;
        $this->req = $req;
        $this->res = $res;
    }

    public function __call($name, $arguments)
    {
        $fn = [$this->res, $name];
        return $fn(...$arguments);
    }

    public function __get($name)
    {
        return $this->res->$name;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case "type":
                return $this->res->header("Content-Type", $value);
            case "lastModified":
                return $this->res->header("Last-Modified", $value);
            case "etag":
                return $this->res->header("ETag", $value);
            case "length":
                return $this->res->header("Content-Length", $value);
            default:
                return $this->res->header($name, $value);
        }
    }

    public function end($html = "")
    {
        if ($this->isEnd) {
            return false;
        }
        $this->isEnd = true;
        return $this->res->end($html);
    }

    public function redirect($url, $status = 302)
    {
        $this->res->header("Location", $url);
        $this->res->header("Content-Type", "text/plain; charset=utf-8");
        $this->ctx->status = $status;
        $this->ctx->body = "Redirecting to $url.";
    }

    public function render($file)
    {
        $this->ctx->body = (yield Template::render($file, $this->ctx->state));
    }
}