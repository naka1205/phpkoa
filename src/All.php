<?php
namespace Naka507\Koa;
class All implements Async
{
    public $parent;
    public $tasks;
    public $continuation;
    public $done;
    public $n;
    public $results;
    public function __construct(array $tasks, AsyncTask $parent = null) {
        $this->tasks = $tasks;
        $this->parent = $parent;
        $this->n = count($tasks);
        $this->results = [];
        $this->done = false;
    }
    public function begin(callable $continuation) {
        $this->continuation = $continuation;
        foreach ($this->tasks as $id => $task) {
            (new AsyncTask($task, $this->parent))->begin($this->continuation($id));
        };
    }
    private function continuation($id) {
        return function($r, $ex = null) use($id) {
            if ($this->done) {
                return;
            }
            if($ex){
                $this->done = true;
                $k = $this->continuation;
                $k(null,$ex);
                return;
            }
            $this->results[$id] = $r;
            if(--$this->n === 0){
                $this->done = true;
                $k = $this->continuation;
                $k($this->results);
            }
        };
    }
}