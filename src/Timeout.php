<?php
namespace Naka507\Koa;
use Naka507\Socket\Timer;
class Timeout implements Middleware
{
    public $times;
    public $exception;

    public function __construct($times, \Exception $ex = null)
    {
        $this->times = $times;
        if ($ex === null) {
            $this->exception = new HttpException(408, "Request timeout");
        } else {
            $this->exception = $ex;
        }
    }

    public function __invoke(Context $ctx, $next)
    {
        yield race([
            callcc(function($k) {
                Timer::add($this->times, function() use($k) {
                    $k(null, $this->exception);
                },[],false);
            }),
            function() use($next) {
                yield $next;
            }
        ]);
    }
}
