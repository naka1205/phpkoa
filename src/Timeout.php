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
                echo "Timer callcc $this->timeout\n";

                $this->timerId = Timer::add($this->timeout, function($that) use($k) {
                    echo "add\n";
                    $that->timerId = null;
                    $k(null, $that->exception);
                },[$this],false);
            }),
            function()use($next){
                yield $next;
            }
        ]);
    }
}
