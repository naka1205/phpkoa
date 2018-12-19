<?php
namespace Naka507\Koa;
interface Async{
    public function begin( callable $callback);
}