<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SovereignStack\SStack\Core\Config\ConfigInterface;
use SovereignStack\SStack\Core\Container\ContainerInterface;

interface KernelInterface
{
    public function boot(): void;

    public function handle(ServerRequestInterface $request): ResponseInterface;

    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;

    public function getState(): KernelState;

    public function getContainer(): ContainerInterface;

    public function getConfig(): ConfigInterface;
}
