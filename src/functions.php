<?php
use Naka507\Koa\Context;
use Naka507\Koa\Syscall;
use Naka507\Koa\CallCC;
use Naka507\Koa\AsyncTask;

function spawn()
{
    $n = func_num_args();
    if ($n === 0) {
        return;
    }

    $task = func_get_arg(0);
    $continuation = function() {};
    $parent = null;
    $ctx = [];

    for ($i = 1; $i < $n; $i++) {
        $arg = func_get_arg($i);
        if (is_callable($arg)) {
            $continuation = $arg;
        } else if ($arg instanceof AsyncTask) {
            $parent = $arg;
        } else if (is_array($arg)) {
            $ctx = $arg;
        }
    }

    if (is_callable($task)) {
        try {
            $task = $task();
        } catch (\Exception $ex) {
            $continuation(null, $ex);
            return;
        }
    }

    if ($task instanceof \Generator) {
        foreach ($ctx as $k => $v) {
            $task->$k = $v;
        }
        (new AsyncTask($task, $parent))->begin($continuation);
    } else {
        $continuation($task, null);
    }
}

function await($task, ...$args)
{
    if ($task instanceof \Generator) {
        return $task;
    }

    if (is_callable($task)) {
        $gen = function() use($task, $args) { yield $task(...$args); };
    } else {
        $gen = function() use($task) { yield $task; };
    }
    return $gen();
}

function race(array $tasks)
{
    $tasks = array_map(__NAMESPACE__ . "\\await", $tasks);

    return new Syscall(function(AsyncTask $parent) use($tasks) {
        if (empty($tasks)) {
            return null;
        } else {
            return new Any($tasks, $parent);
        }
    });
}

function timeout($ms)
{
    return callcc(function($k) use($ms) {
        swoole_timer_after($ms, function() use($k) {
            $k(null, new \Exception("timeout"));
        });
    });
}

function callcc(callable $fun, $timeout = 0)
{
    if ($timeout > 0) {
        $fun = timeoutWrapper($fun, $timeout);
    }
    return new CallCC($fun);
}

function getCtx($key, $default = null)
{
    return new Syscall(function(AsyncTask $task) use($key, $default) {
        while($task->parent && $task = $task->parent);
        if (isset($task->gen->generator->$key)) {
            return $task->gen->generator->$key;
        } else {
            return $default;
        }
    });
}

function setCtx($key, $val)
{
    return new Syscall(function(AsyncTask $task) use($key, $val) {
        while($task->parent && $task = $task->parent);
        $task->gen->generator->$key = $val;
    });
}

function array_right_reduce(array $input, callable $function, $initial = null)
{
    return array_reduce(array_reverse($input, true), $function, $initial);
}

function compose(array $middleware)
{
    return function(Context $ctx = null) use($middleware) {
        $ctx = $ctx ?: new Context();      
        return array_right_reduce($middleware, function($rightNext, $leftFn) use($ctx) {
            return $leftFn($ctx, $rightNext);
        }, null);
    };
}