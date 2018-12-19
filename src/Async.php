<?php
namespace Koa;
interface Async{
    public function begin( callable $callback);
}