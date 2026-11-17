<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\ConfigBuilder;
use SovereignStack\Core\Config\Exception\ConfigurationFileException;
use SovereignStack\Core\Config\Exception\InvalidConfigurationException;

final class ConfigBuilderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../Fixtures/config';
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'APP_') || str_starts_with($key, 'DB_')) {
                unset($_ENV[$key]);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (str_starts_with($key, 'APP_') || str_starts_with($key, 'DB_')) {
                unset($_ENV[$key]);
            }
        }
    }

    // Tests 26-27: NEW (D3 — TOML loading)
    public function testLoadTomlFileMergesIntoBuilder(): void
    {
        $data = (new ConfigBuilder())->loadFile($this->fixturesDir . '/app.toml')->build();
        self::assertSame('SovereignStack', $data['app']['name']);
        self::assertSame('production', $data['app']['env']);
    }

    public function testLoadTomlMergesRecursivelyOnKeyCollision(): void
    {
        $data = (new ConfigBuilder())
            ->loadFile($this->fixturesDir . '/app.toml')
            ->loadFile($this->fixturesDir . '/local.toml')
            ->build();
        self::assertTrue($data['app']['debug']);
        self::assertSame('https://staging.sstack.local', $data['app']['url']);
        self::assertSame('SovereignStack', $data['app']['name']);
    }

    // Test 28: from DGLab source (exception reclassified)
    public function testLoadFileThrowsWhenFileMissing(): void
    {
        $this->expectException(ConfigurationFileException::class);
        (new ConfigBuilder())->loadFile('/nonexistent/config.toml');
    }

    // Tests 29-30: NEW (D3 — TOML parse errors)
    public function testLoadFileThrowsOnMalformedToml(): void
    {
        $this->expectException(ConfigurationFileException::class);
        (new ConfigBuilder())->loadFile($this->fixturesDir . '/malformed.toml');
    }

    public function testLoadFileThrowsOnDuplicateTomlKeys(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'toml_test_');
        file_put_contents($tmpFile, "key = \"value\"\nkey = \"value2\"\n");
        $this->expectException(ConfigurationFileException::class);
        try {
            (new ConfigBuilder())->loadFile($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }

    // Tests 31-32: from DGLab source
    public function testWithOverrideSetsNestedKey(): void
    {
        $data = (new ConfigBuilder())->withOverride('app.debug', true)->build();
        self::assertTrue($data['app']['debug']);
    }

    public function testWithOverrideCreatesIntermediateArrays(): void
    {
        $data = (new ConfigBuilder())->withOverride('a.b.c.d', 'deep')->build();
        self::assertSame('deep', $data['a']['b']['c']['d']);
    }

    // Tests 33-34: from DGLab source
    public function testEnvVarsAreMergedIntoConfig(): void
    {
        $_ENV['APP_NAME'] = 'from-env';
        $data = (new ConfigBuilder())->build();
        self::assertSame('from-env', $data['app']['name']);
    }

    public function testOverridesTakePrecedenceOverEnvVars(): void
    {
        $_ENV['APP_NAME'] = 'from-env';
        $data = (new ConfigBuilder())->withOverride('app.name', 'override')->build();
        self::assertSame('override', $data['app']['name']);
    }

    // Tests 35-36: from DGLab source
    public function testNonStringEnvValuesAreSkipped(): void
    {
        $_ENV['MIXED_INT'] = 123;
        $data = (new ConfigBuilder())->build();
        self::assertArrayNotHasKey('mixed', $data);
        unset($_ENV['MIXED_INT']);
    }

    public function testEmptyStringEnvValueIsSkipped(): void
    {
        $_ENV['EMPTY_VAL'] = '';
        $data = (new ConfigBuilder())->build();
        self::assertArrayNotHasKey('empty', $data);
        unset($_ENV['EMPTY_VAL']);
    }

    // Tests 37-39: from DGLab source (exception reclassified)
    public function testBuildFreezesBuilder(): void
    {
        $builder = new ConfigBuilder();
        $builder->build();
        $this->expectException(InvalidConfigurationException::class);
        $builder->loadFile($this->fixturesDir . '/app.toml');
    }

    public function testWithOverrideAfterBuildThrows(): void
    {
        $builder = new ConfigBuilder();
        $builder->build();
        $this->expectException(InvalidConfigurationException::class);
        $builder->withOverride('key', 'value');
    }

    public function testBuildCanOnlyBeCalledOnce(): void
    {
        $builder = new ConfigBuilder();
        $builder->build();
        $this->expectException(InvalidConfigurationException::class);
        $builder->build();
    }

    // Tests 40-42: from DGLab source
    public function testFluentInterfaceReturnsSameInstance(): void
    {
        $builder = new ConfigBuilder();
        self::assertSame($builder, $builder->withOverride('key', 'value'));
    }

    public function testBuildWithNoDataReturnsEmptyRepository(): void
    {
        $data = (new ConfigBuilder())->build();
        self::assertSame([], $data);
    }

    public function testWithOverrideReplacesExistingValue(): void
    {
        $data = (new ConfigBuilder())
            ->withOverride('app.name', 'first')
            ->withOverride('app.name', 'second')
            ->build();
        self::assertSame('second', $data['app']['name']);
    }

    // Test 43: NEW (D3 — TOML type fidelity)
    public function testTomlPreservesNativeTypes(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'toml_types_');
        file_put_contents($tmpFile, "int_val = 42\nfloat_val = 3.14\nbool_val = true\nstr_val = \"hello\"\n");
        $data = (new ConfigBuilder())->loadFile($tmpFile)->build();
        self::assertSame(42, $data['int_val']);
        self::assertSame(3.14, $data['float_val']);
        self::assertTrue($data['bool_val']);
        self::assertSame('hello', $data['str_val']);
        unlink($tmpFile);
    }
}
