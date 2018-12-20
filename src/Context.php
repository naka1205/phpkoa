<?php
namespace Naka507\Koa;
class Context
{
    public $app;
    public $request;
    public $response;
    public $req;
    public $res;
    public $state = [];
    public $respond = true;
    public $body;
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