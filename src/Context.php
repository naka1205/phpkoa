<?php
namespace Koa;
class Context
{
    public $app;
    /** @var Request */
    public $request;
    /** @var Response */
    public $response;
    /** @var \swoole_http_request */
    public $req;
    /** @var \swoole_http_response */
    public $res;
    public $state = [];
    public $respond = true;
    /** @var string */
    public $body;
    /** @var int */
    public $status;

    public function __call($name, $arguments)
    {
        $fn = [$this->response, $name];
        return $fn(...$arguments);
    }

    public function __get($name)
    {
        return $this->request->$name;
    }

    public function __set($name, $value)
    {
        $this->response->$name = $value;
    }

    public function thrοw($status, $message)
    {
        if ($message instanceof \Exception) {
            $ex = $message;
            throw new HttpException($status, $ex->getMessage(), $ex->getCode(), $ex->getPrevious());
        } else {
            throw new HttpException($status, $message);
        }
    }
}