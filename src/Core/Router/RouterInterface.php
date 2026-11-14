<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Router;

interface RouterInterface
{
    public function addRoute(string $method, string $path, callable|string $handler): void;

    public function match(string $method, string $path): ?RouteMatch;

    public function getRoutes(): array;
}
