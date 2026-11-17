<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Security;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\ConfigBuilder;
use SovereignStack\Core\Config\ConfigRepository;
use SovereignStack\Core\Config\Exception\InvalidConfigurationException;

final class ConfigSecurityTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'TEST_')) {
                unset($_ENV[$key]);
            }
        }
    }

    public function testEnvKeyWithDotDoesNotEscapeNamespace(): void
    {
        $_ENV['TEST.APP.NAME'] = 'injected';
        $data = (new ConfigBuilder())->build();
        self::assertArrayNotHasKey('TEST', $data);
        unset($_ENV['TEST.APP.NAME']);
    }

    public function testOverrideKeyCannotTraverseUpViaEmptySegments(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new ConfigBuilder())->withOverride('app..name', 'injected');
    }

    public function testOverrideKeyCannotBeEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new ConfigBuilder())->withOverride('', 'value');
    }

    public function testGetWithEmptyKeyThrows(): void
    {
        $repo = new ConfigRepository([]);
        $this->expectException(InvalidConfigurationException::class);
        $repo->get('');
    }

    public function testConfigValuesAreNotExecutedAsStringEval(): void
    {
        $repo = new ConfigRepository(['cmd' => 'system("ls")']);
        self::assertSame('system("ls")', $repo->get('cmd'));
    }

    public function testNewlinesInEnvValuesArePreservedNotExecuted(): void
    {
        $_ENV['TEST_NEWLINE'] = "line1\nline2";
        $data = (new ConfigBuilder())->build();
        self::assertSame("line1\nline2", $data['test']['newline'] ?? '');
        unset($_ENV['TEST_NEWLINE']);
    }

    public function testEmptyEnvValueIsSkippedNotTreatedAsNull(): void
    {
        $_ENV['TEST_EMPTY'] = '';
        $data = (new ConfigBuilder())->build();
        self::assertArrayNotHasKey('empty', $data['test'] ?? []);
        unset($_ENV['TEST_EMPTY']);
    }
}
