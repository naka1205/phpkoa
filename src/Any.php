<?php
namespace Naka507\Koa;
class Any implements Async
{
    public $parent;
    public $tasks;
    public $continuation;
    public $done;

    public function __construct(array $tasks, AsyncTask $parent = null)
    {
        assert(!empty($tasks));
        $this->tasks = $tasks;
        $this->parent = $parent;
        $this->done = false;
    }

    public function begin(callable $continuation)
    {
        $this->continuation = $continuation;
        foreach ($this->tasks as $id => $task) {
            (new AsyncTask($task, $this->parent))->begin($this->continuation($id));
        };
    }

    private function continuation($id)
    {
        return function($r, $ex = null) use($id) {
            if ($this->done) {
                return;
            }
            $this->done = true;

            if ($this->continuation) {
                $k = $this->continuation;
                $k($r, $ex);
            }
        };
    }
}