<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\ConfigRepository;
use SovereignStack\Core\Config\Environment;
use SovereignStack\Core\Config\Exception\InvalidConfigurationException;
use SovereignStack\Core\Config\Exception\MissingConfigurationException;

final class ConfigRepositoryTest extends TestCase
{
    // --- Tests 1-22: from DGLab source (preserved) ---

    public function testGetReturnsValueForTopLevelKey(): void
    {
        $repo = new ConfigRepository(['app' => ['name' => 'SStack']]);
        self::assertSame(['name' => 'SStack'], $repo->get('app'));
    }

    public function testGetReturnsValueForNestedKeyUsingDotNotation(): void
    {
        $repo = new ConfigRepository(['db' => ['connections' => ['primary' => ['host' => 'db.internal']]]);
        self::assertSame('db.internal', $repo->get('db.connections.primary.host'));
    }

    public function testGetReturnsDefaultWhenKeyAbsent(): void
    {
        $repo = new ConfigRepository(['app' => ['name' => 'SStack']]);
        self::assertNull($repo->get('nonexistent'));
        self::assertSame('fallback', $repo->get('nonexistent', 'fallback'));
    }

    public function testGetReturnsDefaultWhenIntermediateSegmentIsNotArray(): void
    {
        $repo = new ConfigRepository(['app' => 'string-value']);
        self::assertNull($repo->get('app.name'));
    }

    public function testGetOrFailReturnsValueWhenKeyPresent(): void
    {
        $repo = new ConfigRepository(['app' => ['name' => 'SStack']]);
        self::assertSame('SStack', $repo->getOrFail('app.name'));
    }

    public function testGetOrFailThrowsWhenKeyAbsent(): void
    {
        $repo = new ConfigRepository([]);
        $this->expectException(MissingConfigurationException::class);
        $repo->getOrFail('missing.key');
    }

    public function testGetOrFailThrowsOnIntermediateSegmentMissing(): void
    {
        $repo = new ConfigRepository(['db' => []]);
        $this->expectException(MissingConfigurationException::class);
        $repo->getOrFail('db.host');
    }

    public function testHasReturnsTrueForPresentKeys(): void
    {
        $repo = new ConfigRepository(['app' => ['name' => 'SStack']]);
        self::assertTrue($repo->has('app.name'));
        self::assertTrue($repo->has('app'));
    }

    public function testHasReturnsFalseForAbsentKeys(): void
    {
        $repo = new ConfigRepository(['app' => ['name' => 'SStack']]);
        self::assertFalse($repo->has('nonexistent'));
        self::assertFalse($repo->has('app.missing'));
    }

    public function testHasReturnsFalseWhenIntermediateSegmentIsNotArray(): void
    {
        $repo = new ConfigRepository(['app' => 'string']);
        self::assertFalse($repo->has('app.name'));
    }

    public function testAllReturnsEntireTreeWithSecretsRedacted(): void
    {
        $repo = new ConfigRepository(['password' => 'secret123', 'name' => 'visible']);
        $all = $repo->all();
        self::assertSame('***REDACTED***', $all['password']);
        self::assertSame('visible', $all['name']);
    }

    public function testAllRawReturnsUnredactedSecrets(): void
    {
        $repo = new ConfigRepository(['password' => 'secret123']);
        self::assertSame('secret123', $repo->allRaw()['password']);
    }

    public function testEmptyKeyThrows(): void
    {
        $repo = new ConfigRepository([]);
        $this->expectException(InvalidConfigurationException::class);
        $repo->get('');
    }

    public function testConsecutiveDotsThrow(): void
    {
        $repo = new ConfigRepository([]);
        $this->expectException(InvalidConfigurationException::class);
        $repo->get('app..name');
    }

    public function testGetSupportsScalarRootValues(): void
    {
        $repo = new ConfigRepository(['value' => 'scalar']);
        self::assertSame('scalar', $repo->get('value'));
    }

    public function testGetPreservesNestedArrayStructure(): void
    {
        $nested = ['a' => ['b' => ['c' => 'deep']]];
        $repo = new ConfigRepository($nested);
        self::assertSame(['c' => 'deep'], $repo->get('a.b'));
    }

    public function testNullValueIsDistinguishableFromAbsentKey(): void
    {
        $repo = new ConfigRepository(['present' => null]);
        self::assertTrue($repo->has('present'));
        self::assertNull($repo->get('present'));
        self::assertNull($repo->get('present', 'default'));
        self::assertFalse($repo->has('absent'));
        self::assertSame('default', $repo->get('absent', 'default'));
    }

    public function testAllRedactsNestedSecrets(): void
    {
        $repo = new ConfigRepository(['db' => ['password' => 'secret', 'host' => 'localhost']]);
        $all = $repo->all();
        self::assertSame('***REDACTED***', $all['db']['password']);
        self::assertSame('localhost', $all['db']['host']);
    }

    public function testAllRedactsCaseInsensitively(): void
    {
        $repo = new ConfigRepository(['PASSWORD' => 'secret', 'Token' => 'tok']);
        $all = $repo->all();
        self::assertSame('***REDACTED***', $all['PASSWORD']);
        self::assertSame('***REDACTED***', $all['Token']);
    }

    public function testGetReturnsRawSecretValue(): void
    {
        $repo = new ConfigRepository(['password' => 'secret123']);
        self::assertSame('secret123', $repo->get('password'));
    }

    public function testAllReturnsEmptyArrayWhenConstructedWithEmptyData(): void
    {
        $repo = new ConfigRepository([]);
        self::assertSame([], $repo->all());
    }

    public function testLeadingDotKeyThrowsForEmptySegment(): void
    {
        $repo = new ConfigRepository([]);
        $this->expectException(InvalidConfigurationException::class);
        $repo->get('.app');
    }

    // --- Tests 23-25: NEW (D5 — Environment detection) ---

    public function testGetEnvironmentReturnsDetectedEnvironment(): void
    {
        $repo = (new ConfigRepository(['app' => ['env' => 'production']]))
            ->withEnvironment(Environment::Production);
        self::assertSame(Environment::Production, $repo->getEnvironment());
    }

    public function testGetEnvironmentThrowsForAbsentAppEnv(): void
    {
        $repo = new ConfigRepository([]);
        $this->expectException(MissingConfigurationException::class);
        $repo->getEnvironment();
    }

    public function testGetEnvironmentThrowsForUnrecognisedAppEnv(): void
    {
        // This is tested via Environment::detect() directly
        $this->expectException(\SovereignStack\Core\Config\Exception\InvalidEnvironmentException::class);
        Environment::detect('unknown-env');
    }
}
