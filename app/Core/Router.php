<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    /** @param array{0:class-string,1:string} $handler @param list<class-string> $middleware */
    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[] = [$method, $regex, $handler, $middleware];
    }

    public function get(string $p, array $h, array $mw = []): void
    {
        $this->add('GET', $p, $h, $mw);
    }

    public function post(string $p, array $h, array $mw = []): void
    {
        $this->add('POST', $p, $h, $mw);
    }

    /** @return array{0:array,1:array,2:array}|null [handler, params, middleware] */
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as [$m, $regex, $handler, $mw]) {
            if ($m === $method && preg_match($regex, $path, $mm)) {
                return [$handler, array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY), $mw];
            }
        }
        return null;
    }
}
