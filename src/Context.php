<?php
namespace Naka507\Koa;
class Context
{
    public $app;
    public $request;
    public $response;
    public $req;
    public $res;
    public $funcs = [];
    public $state = [];
    public $respond = true;
    public $body;
    public $view;
    public $status;

    public function __call($name, $arguments)
    {
        if(array_key_exists($name, $this->funcs)){
            return $this->funcs[$name][1](...$arguments);
        }
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

    public function add($func, $callback){
        $this->funcs[$func] = array($func, $callback);
    }
 
    public function accept($name)
    {
        return strpos($this->request->server['http_accept'],$name) === false ?  false : true;
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