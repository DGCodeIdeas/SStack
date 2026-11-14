<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SovereignStack\SStack\Core\Config\ConfigInterface;
use SovereignStack\SStack\Core\Container\ContainerInterface;
use SovereignStack\SStack\Core\Kernel\Event\BootEvent;
use SovereignStack\SStack\Core\Kernel\Event\TerminateEvent;
use Throwable;

final class Kernel implements KernelInterface
{
    private KernelState $state = KernelState::Unbooted;

    /**
     * @param iterable<BootstrapperInterface> $bootstrappers
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigInterface $config,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly iterable $bootstrappers = [],
    ) {}

    public function boot(): void
    {
        if ($this->state !== KernelState::Unbooted) {
            return;
        }

        $this->state = KernelState::Booting;

        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->bootstrap($this);
        }

        $this->eventDispatcher->dispatch(new BootEvent($this));

        $this->state = KernelState::Booted;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->state === KernelState::Unbooted) {
            $this->boot();
        }

        $this->state = KernelState::Handling;

        try {
            $handler = $this->container->get('kernel.request_handler');
            $response = $handler->handle($request);
            $this->state = KernelState::Booted;
            return $response;
        } catch (Throwable $e) {
            $this->state = KernelState::Failed;
            throw $e;
        }
    }

    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        if ($this->state === KernelState::Terminated) {
            return;
        }

        $this->eventDispatcher->dispatch(new TerminateEvent($this, $request, $response));
        $this->state = KernelState::Terminated;
    }

    public function getState(): KernelState
    {
        return $this->state;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}
