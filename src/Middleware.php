<?php
namespace Naka507\Koa;
interface Middleware
{
    public function __invoke(Context $ctx, $next);
}