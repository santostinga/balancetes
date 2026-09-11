<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    /** @var array<string, array<int, array{pattern:string,handler:callable}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                $args = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                ($route['handler'])(...array_values($args));
                return;
            }
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Página não encontrada'], 'layout');
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '$#';
        if ($path === '/') {
            $pattern = '#^/$#';
        }
        $this->routes[$method][] = ['pattern' => $pattern, 'handler' => $handler];
    }
}
