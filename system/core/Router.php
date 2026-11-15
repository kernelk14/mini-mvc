<?php
/**
 * Minimal router with registry and dispatch.
 * PHP 5.0+ compatible.
 */
class Core_Router
{
    static $routes = array();

    static function add($method, $path, $handler)
    {
        $method = strtoupper($method);
        Core_Router::$routes[] = array(
            'method' => $method,
            'path'   => $path,
            'handler'=> $handler,
        );
    }

    function getRoutes()
    {
        return Core_Router::$routes;
    }

    function dispatch($method, $uri)
    {
        $method = strtoupper($method);
        $path = $this->extractPath($uri);
        $i = 0;
        $count = count(Core_Router::$routes);
        while ($i < $count) {
            $r = Core_Router::$routes[$i];
            $m = $r['method'];
            if ($m === $method || $m === 'ANY') {
                $params = $this->matchPath($r['path'], $path);
                if ($params !== false) {
                    $parsed = $this->parseHandler($r['handler']);
                    return array(
                        'controller' => $parsed['controller'],
                        'action'     => $parsed['action'],
                        'params'     => $params,
                    );
                }
            }
            $i++;
        }

        // Fallback: /Controller/action/param1/param2
        $parts = explode('/', trim($path, '/'));
        $controller = 'HomeController';
        $action = 'index';
        $params = array();

        if (isset($parts[0]) && $parts[0] !== '') {
            $controller = ucfirst($parts[0]) . 'Controller';
        }
        if (isset($parts[1]) && $parts[1] !== '') {
            $action = $parts[1];
        }
        if (count($parts) > 2) {
            $params = array_slice($parts, 2);
        }

        return array(
            'controller' => $controller,
            'action'     => $action,
            'params'     => $params,
        );
    }

    function matchPath($routePath, $requestPath)
    {
        $routePath = (string)$routePath;
        $requestPath = (string)$requestPath;
        if ($routePath === $requestPath) {
            return array();
        }
        if (strpos($routePath, '{') === false) {
            return false;
        }

        $routeTrim = trim($routePath, '/');
        $reqTrim = trim($requestPath, '/');
        if ($routeTrim === '' || $reqTrim === '') {
            return false;
        }
        $rParts = explode('/', $routeTrim);
        $pParts = explode('/', $reqTrim);
        if (count($rParts) !== count($pParts)) {
            return false;
        }

        $params = array();
        $i = 0;
        while ($i < count($rParts)) {
            $r = $rParts[$i];
            $p = $pParts[$i];
            $len = strlen($r);
            if ($len >= 2 && $r[0] === '{' && $r[$len - 1] === '}') {
                if ($p === '') { return false; }
                $params[] = $p;
            } elseif ($r !== $p) {
                return false;
            }
            $i++;
        }
        return $params;
    }

    function extractPath($uri)
    {
        $path = $uri;
        if (function_exists('parse_url')) {
            $p = @parse_url($uri, PHP_URL_PATH);
            if ($p !== null && $p !== false) {
                $path = $p;
            } else {
                $qpos = strpos($uri, '?');
                if ($qpos !== false) {
                    $path = substr($uri, 0, $qpos);
                }
            }
        } else {
            $qpos = strpos($uri, '?');
            if ($qpos !== false) {
                $path = substr($uri, 0, $qpos);
            }
        }
        if ($path === '' || $path === null) {
            $path = '/';
        }
        // normalize trailing slash (except root)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        return $path;
    }

    function parseHandler($handler)
    {
        $controller = 'HomeController';
        $action = 'index';
        if (is_string($handler)) {
            $pos = strpos($handler, '@');
            if ($pos !== false) {
                $controller = substr($handler, 0, $pos);
                $action = substr($handler, $pos + 1);
            } else {
                $controller = $handler;
            }
        } elseif (is_array($handler)) {
            if (isset($handler[0])) { $controller = $handler[0]; }
            if (isset($handler[1])) { $action = $handler[1]; }
        }
        if ($controller === '') { $controller = 'HomeController'; }
        if ($action === '') { $action = 'index'; }
        return array('controller' => $controller, 'action' => $action);
    }
}
