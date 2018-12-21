<?php
namespace Naka507\Koa;
use Naka507\Socket\Timer;
class Timeout implements Middleware
{
    public $timeout;
    public $exception;

    private $timerId;

    public function __construct($timeout, \Exception $ex = null)
    {
        $this->timeout = $timeout;
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
                $this->timerId = Timer::add($this->timeout, function() use($k) {
                    $this->timerId = null;
                    $k(null, $this->exception);
                },[],false);
            }),
            function() use($next) {
                yield $next;
            }
        ]);
    }
}
