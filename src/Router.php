<?php
namespace Naka507\Koa;

class Router
{

    private $afterRoutes = array();
    private $beforeRoutes = array();
    protected $notFoundCallback;
    private $baseRoute = '';
    private $requestedMethod = '';
    private $serverBasePath;
    private $namespace = '';


    public $dispatcher;

    public function __construct()
    {

    }

    // 返回路由中间件
    public function routes()
    {
        return [$this, "dispatch"];
    }


    public function dispatch(Context $ctx, $next)
    {
        $this->requestedMethod = $this->getRequestMethod();

        $handled = 0;
        if (isset($this->afterRoutes[$this->requestedMethod])) {
            $route = $this->handle($this->afterRoutes[$this->requestedMethod]);
            $handled = $route['handled'];
        }
        
        switch ($handled) {
            case 0:
                // 状态码写入Context
                $ctx->status = 404;
                yield $next;
                break;
            
            default:
                $fn = $route['fn'];
                $vars = $route['vars'];
                // 从路由表提取处理器
                yield $fn($ctx, $next, $vars);
                break;
        }
    }


    public function before($methods, $pattern, $fn)
    {
        $pattern = $this->baseRoute.'/'.trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;
        foreach (explode('|', $methods) as $method) {
            $this->beforeRoutes[$method][] = array(
                'pattern' => $pattern,
                'fn' => $fn,
            );
        }
    }

    public function match($methods, $pattern, $fn)
    {
        $pattern = $this->baseRoute.'/'.trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;
        foreach (explode('|', $methods) as $method) {
            $this->afterRoutes[$method][] = array(
                'pattern' => $pattern,
                'fn' => $fn,
            );
        }
    }

    public function all($pattern, $fn)
    {
        $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $fn);
    }

    public function get($pattern, $fn)
    {
        $this->match('GET', $pattern, $fn);
    }

    public function post($pattern, $fn)
    {
        $this->match('POST', $pattern, $fn);
    }

    public function patch($pattern, $fn)
    {
        $this->match('PATCH', $pattern, $fn);
    }

    public function delete($pattern, $fn)
    {
        $this->match('DELETE', $pattern, $fn);
    }

    public function put($pattern, $fn)
    {
        $this->match('PUT', $pattern, $fn);
    }

    public function options($pattern, $fn)
    {
        $this->match('OPTIONS', $pattern, $fn);
    }

    public function mount($baseRoute, $fn)
    {
        $curBaseRoute = $this->baseRoute;
        $this->baseRoute .= $baseRoute;
        call_user_func($fn);
        $this->baseRoute = $curBaseRoute;
    }

    public function getRequestHeaders()
    {
        $headers = array();
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return $headers;
            }
        }
        foreach ($_SERVER as $name => $value) {
            if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
                $headers[str_replace(array(' ', 'Http'), array('-', 'HTTP'), ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function getRequestMethod()
    {
        $method = $_SERVER['REQUEST_METHOD']; 
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
            $method = 'GET';
        }elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getRequestHeaders();
            if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], array('PUT', 'DELETE', 'PATCH'))) {
                $method = $headers['X-HTTP-Method-Override'];
            }
        }
        return $method;
    }

    public function notFound($fn)
    {
        $this->notFoundCallback = $fn;
    }

    private function handle($routes)
    {
        $fn = null;
        $vars = null;
        $handled = 0;
        $uri = $this->getCurrentUri();
        foreach ($routes as $route) {
            $route['pattern'] = preg_replace('/{([A-Za-z]*?)}/', '(\w+)', $route['pattern']);
            if (preg_match_all('#^'.$route['pattern'].'$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {
                $matches = array_slice($matches, 1);
                $params = array_map(function ($match, $index) use ($matches) {
                    if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                        return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    }else {
                        return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                    }
                }, $matches, array_keys($matches));

                $fn = $route['fn'];
                $vars = $params;
                ++$handled;
                break;
            }
        }
        return ['handled' => $handled,'fn' => $fn,'vars' => $vars];
    }

    protected function getCurrentUri()
    {
        $uri = substr($_SERVER['REQUEST_URI'], strlen($this->getBasePath()));
        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        return '/'.trim($uri, '/');
    }

    protected function getBasePath()
    {
        if ($this->serverBasePath === null) {
            $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)).'/';
        }
        return $this->serverBasePath;
    }
}