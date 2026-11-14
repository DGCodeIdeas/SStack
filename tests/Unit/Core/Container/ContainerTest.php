<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Tests\Unit\Core\Container;

use PHPUnit\Framework\TestCase;
use SovereignStack\SStack\Core\Container\Container;
use SovereignStack\SStack\Core\Container\NotFoundException;

final class ContainerTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $container = new Container();
        $container->set('scalar', 42);
        $this->assertSame(42, $container->get('scalar'));
    }

    public function testHas(): void
    {
        $container = new Container();
        $container->set('present', 'value');
        $this->assertTrue($container->has('present'));
        $this->assertFalse($container->has('absent'));
    }

    public function testGetThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $container = new Container();
        $container->get('nonexistent');
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton('counter', fn() => new \stdClass());
        $a = $container->get('counter');
        $b = $container->get('counter');
        $this->assertSame($a, $b);
    }

    public function testFactoryReturnsNewInstance(): void
    {
        $container = new Container();
        $container->factory('counter', fn() => new \stdClass());
        $a = $container->get('counter');
        $b = $container->get('counter');
        $this->assertNotSame($a, $b);
    }

    public function testTagging(): void
    {
        $container = new Container();
        $container->set('service.a', 'a');
        $container->set('service.b', 'b');
        $container->tag('service.a', 'services');
        $container->tag('service.b', 'services');

        $this->assertSame(['service.a', 'service.b'], $container->tagged('services'));
    }

    public function testAutowireSimpleClass(): void
    {
        $container = new Container();
        $instance = $container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }
}
