<?php

namespace App;

class Router
{
    private $routes = [];
    private $currentMethod = '';
    private $currentPath = '';

    public function post($path, $controller)
    {
        $this->addRoute('POST', $path, $controller);
    }

    public function get($path, $controller)
    {
        $this->addRoute('GET', $path, $controller);
    }

    public function put($path, $controller)
    {
        $this->addRoute('PUT', $path, $controller);
    }

    public function delete($path, $controller)
    {
        $this->addRoute('DELETE', $path, $controller);
    }

    private function addRoute($method, $path, $controller)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller
        ];
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($this->matchRoute($requestMethod, $requestPath, $route)) {
                $this->executeRoute($route);
                return;
            }
        }

        // Route not found
        http_response_code(404);
        echo json_encode(['error' => 'Route not found', 'path' => $requestPath, 'method' => $requestMethod]);
    }

    private function matchRoute($method, $path, $route)
    {
        if ($route['method'] !== $method) {
            return false;
        }

        $routePath = $route['path'];
        $pattern = preg_replace('/:(\w+)/', '(?<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            $this->currentMethod = $method;
            $this->currentPath = $path;
            $_GET = array_merge($_GET, array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            return true;
        }

        return false;
    }

    private function executeRoute($route)
    {
        list($controller, $action) = explode('@', $route['controller']);
        $controllerClass = 'App\\Controllers\\' . $controller;

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo json_encode(['error' => 'Controller not found: ' . $controllerClass]);
            return;
        }

        $instance = new $controllerClass();
        if (!method_exists($instance, $action)) {
            http_response_code(500);
            echo json_encode(['error' => 'Action not found: ' . $action]);
            return;
        }

        call_user_func([$instance, $action]);
    }
}
