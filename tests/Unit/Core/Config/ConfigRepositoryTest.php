<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Tests\Unit\Core\Config;

use PHPUnit\Framework\TestCase;
use SovereignStack\SStack\Core\Config\ConfigRepository;

final class ConfigRepositoryTest extends TestCase
{
    public function testGetExistingKey(): void
    {
        $config = new ConfigRepository(['app' => ['name' => 'SStack', 'version' => '1.0']]);
        $this->assertSame('SStack', $config->get('app.name'));
        $this->assertSame('1.0', $config->get('app.version'));
    }

    public function testGetNonExistingKeyReturnsDefault(): void
    {
        $config = new ConfigRepository([]);
        $this->assertNull($config->get('missing'));
        $this->assertSame('default', $config->get('missing', 'default'));
    }

    public function testHas(): void
    {
        $config = new ConfigRepository(['present' => true]);
        $this->assertTrue($config->has('present'));
        $this->assertFalse($config->has('absent'));
    }

    public function testSet(): void
    {
        $config = new ConfigRepository([]);
        $config->set('app.name', 'SStack');
        $this->assertSame('SStack', $config->get('app.name'));
    }

    public function testAll(): void
    {
        $data = ['a' => 1, 'b' => ['c' => 2]];
        $config = new ConfigRepository($data);
        $this->assertSame($data, $config->all());
    }
}
