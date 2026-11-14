<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Tests\Unit\Core\EventDispatcher;

use PHPUnit\Framework\TestCase;
use SovereignStack\SStack\Core\EventDispatcher\EventDispatcher;
use SovereignStack\SStack\Core\EventDispatcher\ListenerProvider;

final class EventDispatcherTest extends TestCase
{
    public function testDispatchCallsListener(): void
    {
        $provider = new ListenerProvider();
        $called = false;
        $provider->addListener(\stdClass::class, function (object $event) use (&$called): void {
            $called = true;
        });

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new \stdClass());

        $this->assertTrue($called);
    }

    public function testDispatchReturnsEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);
        $event = new \stdClass();
        $result = $dispatcher->dispatch($event);
        $this->assertSame($event, $result);
    }

    public function testMultipleListenersCalled(): void
    {
        $provider = new ListenerProvider();
        $count = 0;
        $provider->addListener(\stdClass::class, function () use (&$count): void { $count++; });
        $provider->addListener(\stdClass::class, function () use (&$count): void { $count++; });
        $provider->addListener(\stdClass::class, function () use (&$count): void { $count++; });

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new \stdClass());

        $this->assertSame(3, $count);
    }
}
