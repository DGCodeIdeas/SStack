<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\EnvLoader;
use SovereignStack\Core\Config\Exception\ConfigurationFileException;

final class EnvLoaderTest extends TestCase
{
    private string $envFile;

    protected function setUp(): void
    {
        $this->envFile = __DIR__ . '/../Fixtures/config/.env.test';
        // Clean all test-relevant keys for deterministic runs
        foreach (array_keys($_ENV) as $key) {
            if (preg_match('/^(APP_|DB_|BASE_URL|SINGLE_QUOTED|DOUBLE_QUOTED|BARE_VALUE|EXPORTED_KEY|IGNORED_KEY|KEY|MIXED_INT|EMPTY_VAL|TEST_)/', $key)) {
                unset($_ENV[$key]);
            }
        }
        $_ENV['BASE_URL'] = 'sstack.example.com';
    }

    protected function tearDown(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (preg_match('/^(APP_|DB_|BASE_URL|SINGLE_QUOTED|DOUBLE_QUOTED|BARE_VALUE|EXPORTED_KEY|IGNORED_KEY|KEY|MIXED_INT|EMPTY_VAL|TEST_)/', $key)) {
                unset($_ENV[$key]);
            }
        }
    }

    public function testLoadParsesKeyValuePairs(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertSame('SovereignStack', $loaded['APP_NAME']);
    }

    public function testLoadWritesToEnvSuperglobal(): void
    {
        (new EnvLoader())->load($this->envFile);
        self::assertSame('SovereignStack', $_ENV['APP_NAME']);
    }

    public function testLoadInterpolatesDoubleQuotedValues(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertSame('prefix-SovereignStack-suffix', $loaded['DOUBLE_QUOTED']);
    }

    public function testLoadInterpolatesReferencesToExistingEnvVars(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertSame('https://sstack.example.com/api', $loaded['APP_URL']);
    }

    public function testLoadDoesNotInterpolateSingleQuotedValues(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertSame('${NOT_EXPANDED}', $loaded['SINGLE_QUOTED']);
    }

    public function testLoadHandlesBareValues(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertSame('hello-world', $loaded['BARE_VALUE']);
    }

    public function testLoadHandlesExportPrefix(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertSame('exported-value', $loaded['EXPORTED_KEY']);
    }

    public function testLoadSkipsCommentLines(): void
    {
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertArrayNotHasKey('IGNORED_KEY', $loaded);
    }

    public function testLoadDoesNotOverwriteExistingEnvVars(): void
    {
        $_ENV['APP_NAME'] = 'pre-existing';
        $loaded = (new EnvLoader())->load($this->envFile);
        self::assertArrayNotHasKey('APP_NAME', $loaded);
        self::assertSame('pre-existing', $_ENV['APP_NAME']);
    }

    public function testLoadThrowsWhenFileMissing(): void
    {
        $this->expectException(ConfigurationFileException::class);
        (new EnvLoader())->load('/nonexistent/.env');
    }

    public function testLoadThrowsOnMalformedLineWithoutEquals(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, "KEY_WITHOUT_EQUALS\n");
        $this->expectException(ConfigurationFileException::class);
        try {
            (new EnvLoader())->load($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }

    public function testLoadThrowsOnInvalidKeyName(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, "invalid-key = value\n");
        $this->expectException(ConfigurationFileException::class);
        try {
            (new EnvLoader())->load($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }

    public function testLoadHandlesBlankLinesAndComments(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, "\n# comment\nKEY=value\n\n");
        $loaded = (new EnvLoader())->load($tmpFile);
        self::assertSame('value', $loaded['KEY']);
        unlink($tmpFile);
    }

    public function testLoadHandlesCRLFLineEndings(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, "KEY=value\r\nKEY2=value2\r\n");
        $loaded = (new EnvLoader())->load($tmpFile);
        self::assertSame('value', $loaded['KEY']);
        self::assertSame('value2', $loaded['KEY2']);
        unlink($tmpFile);
    }

    public function testUndefinedInterpolationLeftLiteral(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, 'KEY=${UNDEFINED_VAR}' . "\n");
        $loaded = (new EnvLoader())->load($tmpFile);
        self::assertSame('${UNDEFINED_VAR}', $loaded['KEY']);
        unlink($tmpFile);
    }

    public function testLoadReturnsEmptyArrayForEmptyFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, '');
        $loaded = (new EnvLoader())->load($tmpFile);
        self::assertSame([], $loaded);
        unlink($tmpFile);
    }

    public function testLoadReturnsEmptyArrayForCommentsOnly(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, "# comment\n# another\n");
        $loaded = (new EnvLoader())->load($tmpFile);
        self::assertSame([], $loaded);
        unlink($tmpFile);
    }
}
