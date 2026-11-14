<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Router;

final class Router implements RouterInterface
{
    /** @var array<array{method: string, regex: string, params: array<string>, handler: string, closure: callable|null}> */
    private array $routes = [];

    public function addRoute(string $method, string $path, callable|string $handler): void
    {
        $method = strtoupper($method);
        [$regex, $params] = $this->compilePattern($path);
        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'params' => $params,
            'handler' => is_string($handler) ? $handler : '{closure}',
            'closure' => is_callable($handler) ? $handler : null,
        ];
    }

    public function match(string $method, string $path): ?RouteMatch
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($route['params'] as $paramName) {
                    if (isset($matches[$paramName])) {
                        $params[$paramName] = $matches[$paramName];
                    }
                }

                $handler = $route['handler'];
                if ($handler === '{closure}' && $route['closure'] !== null) {
                    return new RouteMatch('{closure}', $params);
                }

                return new RouteMatch($handler, $params);
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return array{0: string, 1: array<string>}
     */
    private function compilePattern(string $path): array
    {
        $params = [];

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $matches) use (&$params): string {
                $params[] = $matches[1];
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $path,
        );

        return ['#^' . $regex . '$#', $params];
    }
}
