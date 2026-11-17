<?php
/**
 * Application bootstrap and dispatcher.
 */
class Core_App
{
    var $router;
    var $config;

    function __construct()
    {
        $this->Core_App();
    }

    function Core_App()
    {
        $this->config = mini_config('app');
        $this->router = new Core_Router();
    }

    function run()
    {
        $this->sendSecurityHeaders();
        $this->loadRoutes();

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $route = $this->router->dispatch($method, $uri);
        $this->dispatchRoute($route, $method);
    }

    function sendSecurityHeaders()
    {
        if (headers_sent()) { return; }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('X-XSS-Protection: 1; mode=block');
    }

    function loadRoutes()
    {
        $routesFile = mini_base_path() . '/config/routes.php';
        if (is_file($routesFile)) {
            require $routesFile;
        }
    }

    function dispatchRoute($route, $method = null)
    {
        if ($method === null) {
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }
        $method = strtoupper($method);

        $status = isset($route['status']) ? (int)$route['status'] : 200;
        if ($status === 405) {
            $allowed = isset($route['allowed']) && is_array($route['allowed']) ? $route['allowed'] : array();
            $this->methodNotAllowed($allowed);
            return;
        }

        $controllerName = isset($route['controller']) ? $route['controller'] : 'HomeController';
        $action = isset($route['action']) ? $route['action'] : 'index';
        $params = isset($route['params']) && is_array($route['params']) ? $route['params'] : array();

        if (!$this->isValidController($controllerName)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerName();
        if (!$this->isValidAction($controller, $action)) {
            $this->notFound();
            return;
        }

        // Automatic CSRF for mutating requests when enabled
        if (isset($this->config['csrf_protect']) && $this->config['csrf_protect']) {
            $mutating = array('POST','PUT','PATCH','DELETE');
            if (in_array($method, $mutating)) {
                $token = isset($_POST['_csrf']) ? $_POST['_csrf'] : null;
                if (function_exists('csrf_valid') && !csrf_valid($token)) {
                    $this->forbidden('Invalid CSRF token');
                    return;
                }
            }
        }

        if ($method === 'HEAD') {
            ob_start();
            call_user_func_array(array($controller, $action), $params);
            ob_end_clean();
            return;
        }

        call_user_func_array(array($controller, $action), $params);
    }

    function methodNotAllowed($allowed)
    {
        if (function_exists('http_response_code')) {
            http_response_code(405);
        } else {
            header('HTTP/1.1 405 Method Not Allowed');
        }
        if (is_array($allowed) && count($allowed) > 0) {
            header('Allow: ' . implode(', ', $allowed));
        }
        echo '<h1>405 Method Not Allowed</h1>';
    }

    function forbidden($message)
    {
        if (function_exists('http_response_code')) {
            http_response_code(403);
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
        $debug = function_exists('mini_debug') ? mini_debug() : false;
        $msg = ($debug && $message) ? $message : 'Forbidden';
        echo '<h1>403 Forbidden</h1><p>' . htmlspecialchars($msg, ENT_QUOTES) . '</p>';
    }

    function isValidController($name)
    {
        if (!is_string($name) || $name === '') { return false; }
        if (!preg_match('/^[A-Za-z0-9_]+Controller$/', $name)) { return false; }
        if (!class_exists($name)) { return false; }
        if (function_exists('is_subclass_of')) {
            if (!is_subclass_of($name, 'Core_Controller')) { return false; }
        }
        return true;
    }

    function isValidAction($controller, $action)
    {
        if (!is_string($action) || $action === '') { return false; }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $action)) { return false; }
        if ($action[0] === '_') { return false; }
        $blocked = array('db', 'view', 'render', 'redirect', 'init', '__construct', 'core_controller', 'core_app');
        $lower = strtolower($action);
        $i = 0;
        while ($i < count($blocked)) {
            if ($lower === $blocked[$i]) { return false; }
            $i++;
        }
        if (!method_exists($controller, $action)) { return false; }
        if (!is_callable(array($controller, $action))) { return false; }
        return true;
    }

    function notFound($message = null)
    {
        if ($message === null) { $message = ''; }
        if (function_exists('http_response_code')) {
            http_response_code(404);
        } else {
            header('HTTP/1.1 404 Not Found');
        }
        $viewFile = mini_base_path() . '/app/views/errors/404.php';
        $debug = function_exists('mini_debug') ? mini_debug() : false;
        $error_message = ($debug && $message !== '') ? $message : 'The requested page could not be found.';
        if (is_file($viewFile)) {
            include $viewFile;
        } else {
            echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($error_message, ENT_QUOTES) . '</p>';
        }
    }
}
