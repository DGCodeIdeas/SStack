<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Router;

final class RouteMatch
{
    /**
     * @param array<string, string> $params
     */
    public function __construct(
        private readonly string $handler,
        private readonly array $params = [],
    ) {}

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $name, ?string $default = null): ?string
    {
        return $this->params[$name] ?? $default;
    }
}
