<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\ConfigLoader;
use SovereignStack\Core\Config\Environment;
use SovereignStack\Core\Config\Exception\ConfigurationFileException;
use SovereignStack\Core\Config\Exception\InvalidEnvironmentException;
use SovereignStack\Core\Config\Exception\MissingConfigurationException;

final class ConfigLoaderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../Fixtures/config';
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'APP_') || str_starts_with($key, 'DB_') || str_starts_with($key, 'BASE_URL')) {
                unset($_ENV[$key]);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'APP_') || str_starts_with($key, 'DB_') || str_starts_with($key, 'BASE_URL')) {
                unset($_ENV[$key]);
            }
        }
    }

    public function testLoadProducesConfigRepository(): void
    {
        $config = (new ConfigLoader())->load(
            configFiles: [$this->fixturesDir . '/app.toml'],
            envFile: $this->fixturesDir . '/.env.test',
            overrides: ['app.debug' => false],
        );
        self::assertSame('SovereignStack', $config->get('app.name'));
        self::assertFalse($config->get('app.debug'));
    }

    public function testLoadPropagatesTomlParseErrors(): void
    {
        $this->expectException(ConfigurationFileException::class);
        (new ConfigLoader())->load(
            configFiles: [$this->fixturesDir . '/malformed.toml'],
        );
    }

    public function testLoadPropagatesEnvironmentDetectionErrors(): void
    {
        $this->expectException(InvalidEnvironmentException::class);
        (new ConfigLoader())->load(
            configFiles: [],
            overrides: ['app.env' => 'unknown-env'],
        );
    }

    public function testLoadPropagatesValidationErrors(): void
    {
        $this->expectException(MissingConfigurationException::class);
        (new ConfigLoader())->load(
            configFiles: [$this->fixturesDir . '/app.toml'],
            overrides: ['app.env' => 'production'],
            schema: ['required' => ['nonexistent.key' => 'string']],
        );
    }

    public function testLoadWithEmptyConfigProducesEnvironmentError(): void
    {
        $this->expectException(InvalidEnvironmentException::class);
        (new ConfigLoader())->load();
    }
}
