<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Tests\Unit\Core\Router;

use PHPUnit\Framework\TestCase;
use SovereignStack\SStack\Core\Router\RouteMatch;
use SovereignStack\SStack\Core\Router\Router;

final class RouterTest extends TestCase
{
    public function testStaticRouteMatch(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/hello', 'HelloHandler');

        $match = $router->match('GET', '/hello');
        $this->assertNotNull($match);
        $this->assertSame('HelloHandler', $match->getHandler());
        $this->assertSame([], $match->getParams());
    }

    public function testRouteWithParam(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/users/{id}', 'UserHandler');

        $match = $router->match('GET', '/users/42');
        $this->assertNotNull($match);
        $this->assertSame('UserHandler', $match->getHandler());
        $this->assertSame('42', $match->getParam('id'));
    }

    public function testRouteWithMultipleParams(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/users/{user_id}/posts/{post_id}', 'PostHandler');

        $match = $router->match('GET', '/users/42/posts/100');
        $this->assertNotNull($match);
        $this->assertSame('42', $match->getParam('user_id'));
        $this->assertSame('100', $match->getParam('post_id'));
    }

    public function testNoMatchReturnsNull(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/hello', 'HelloHandler');

        $this->assertNull($router->match('GET', '/goodbye'));
    }

    public function testMethodMismatchReturnsNull(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/hello', 'HelloHandler');

        $this->assertNull($router->match('POST', '/hello'));
    }
}
