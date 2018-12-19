<?php
namespace Naka507\Koa;
class Gen{
    
    public $isfirst = true;
    public $id;
    public $generator;

    public function __construct(\Generator $generator,$id=0){
        $this->id = $id;
        $this->generator = $generator;
    }

    public function throws(\Exception $ex){
        return $this->generator->throw($ex);
    }

    public function valid(){
        return $this->generator->valid();
    }

    public function send($value = null){
        
        if( $this->isfirst ){
            $this->isfirst = false;
            $result = $this->generator->current();
        }else{
            $result = $this->generator->send($value);
        }
        return $result;
    }
}