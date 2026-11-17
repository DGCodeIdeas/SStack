<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Tests\Performance;

use PHPUnit\Framework\TestCase;
use SovereignStack\Core\Config\ConfigBuilder;
use SovereignStack\Core\Config\ConfigRepository;

final class ConfigBenchTest extends TestCase
{
    private ConfigRepository $config;

    protected function setUp(): void
    {
        $tree = [];
        for ($i = 0; $i < 50; $i++) {
            $tree["section{$i}"] = [
                'subsection' => [
                    'leaf' => "value-{$i}",
                    'nested' => ['deep' => ['path' => "deep-{$i}"]],
                ],
            ];
        }
        $this->config = new ConfigRepository($tree);
    }

    public function testNestedKeyResolutionUnderTenMicroseconds(): void
    {
        $start = hrtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $this->config->get('section25.subsection.nested.deep.path');
        }
        $elapsedNs = hrtime(true) - $start;
        $meanNs = $elapsedNs / 10000;
        self::assertLessThan(10000, $meanNs, "Mean resolution: {$meanNs}ns (target: < 10000ns = 10µs)");
    }

    public function testHasCheckUnderTenMicroseconds(): void
    {
        $start = hrtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $this->config->has('section25.subsection.leaf');
        }
        $elapsedNs = hrtime(true) - $start;
        $meanNs = $elapsedNs / 10000;
        self::assertLessThan(10000, $meanNs, "Mean has: {$meanNs}ns (target: < 10000ns = 10µs)");
    }

    public function testMissLookupUnderTenMicroseconds(): void
    {
        $start = hrtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $this->config->get('nonexistent.deeply.nested.key');
        }
        $elapsedNs = hrtime(true) - $start;
        $meanNs = $elapsedNs / 10000;
        self::assertLessThan(10000, $meanNs, "Mean miss: {$meanNs}ns (target: < 10000ns = 10µs)");
    }

    public function testBuilderBuildTimeAcceptableForLargeConfig(): void
    {
        $builder = new ConfigBuilder();
        for ($i = 0; $i < 100; $i++) {
            $builder->withOverride("section{$i}.key", "value{$i}");
        }
        $start = hrtime(true);
        $builder->build();
        $elapsedNs = hrtime(true) - $start;
        $elapsedMs = $elapsedNs / 1_000_000;
        self::assertLessThan(50.0, $elapsedMs, "Build time: {$elapsedMs}ms (target: < 50ms)");
    }
}
