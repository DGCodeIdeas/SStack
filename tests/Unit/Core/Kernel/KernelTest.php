<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Tests\Unit\Core\Kernel;

use PHPUnit\Framework\TestCase;
use SovereignStack\SStack\Core\Config\ConfigRepository;
use SovereignStack\SStack\Core\Container\Container;
use SovereignStack\SStack\Core\EventDispatcher\EventDispatcher;
use SovereignStack\SStack\Core\EventDispatcher\ListenerProvider;
use SovereignStack\SStack\Core\Kernel\Kernel;
use SovereignStack\SStack\Core\Kernel\KernelState;

final class KernelTest extends TestCase
{
    public function testInitialStateIsUnbooted(): void
    {
        $kernel = $this->createKernel();
        $this->assertSame(KernelState::Unbooted, $kernel->getState());
    }

    public function testBootTransitionsToBooted(): void
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        $this->assertSame(KernelState::Booted, $kernel->getState());
    }

    public function testBootIsIdempotent(): void
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        $kernel->boot();
        $this->assertSame(KernelState::Booted, $kernel->getState());
    }

    public function testGetContainer(): void
    {
        $container = new Container();
        $kernel = $this->createKernel($container);
        $this->assertSame($container, $kernel->getContainer());
    }

    public function testGetConfig(): void
    {
        $config = new ConfigRepository(['test' => 'value']);
        $kernel = $this->createKernel(config: $config);
        $this->assertSame($config, $kernel->getConfig());
        $this->assertSame('value', $kernel->getConfig()->get('test'));
    }

    private function createKernel(?Container $container = null, ?ConfigRepository $config = null): Kernel
    {
        $container ??= new Container();
        $config ??= new ConfigRepository();
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        return new Kernel($container, $config, $dispatcher);
    }
}
