<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function set(string $id, mixed $value): void;

    public function singleton(string $id, callable|string $factory): void;

    public function factory(string $id, callable|string $factory): void;

    public function tag(string $id, string $tag): void;

    /**
     * @return array<string>
     */
    public function tagged(string $tag): array;
}
