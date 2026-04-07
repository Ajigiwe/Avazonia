<?php
// core/Router.php
class Router {
    protected $routes = [];

    public function add($method, $uri, $controller) {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller
        ];
    }

    public function dispatch($uri, $method) {
        foreach ($this->routes as $route) {
            $pattern = $this->getRegex($route['uri']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove the full URI match
                
                list($controllerName, $action) = explode('@', $route['controller']);
                require_once __DIR__ . "/../controllers/$controllerName.php";
                
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $action)) {
                        call_user_func_array([$controller, $action], $matches);
                        return;
                    }
                }
            }
        }
        $this->error(404);
    }

    protected function getRegex($routeUri) {
        $routeUri = preg_replace('/\{[a-z]+\}/', '([a-z0-9-]+)', $routeUri);
        $routeUri = str_replace('/', '\/', $routeUri);
        return '/^' . $routeUri . '$/i';
    }

    protected function error($code) {
        http_response_code($code);
        // Include 404 view if exists
        echo "404 Not Found";
    }
}
