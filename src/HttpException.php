<?php
namespace Naka507\Koa;
class HttpException extends \RuntimeException
{
    private $status;
    private $headers;

    public function __construct($status, $message = null, $code = 0 , \Exception $previous = null, array $headers = [])
    {
        $this->status = $status;
        $this->headers    = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
