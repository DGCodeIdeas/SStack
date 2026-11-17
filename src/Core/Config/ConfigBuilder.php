<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

use SovereignStack\Core\Config\Exception\ConfigurationFileException;
use SovereignStack\Core\Config\Exception\InvalidConfigurationException;
use SovereignStack\Core\Config\Toml\TomlParser;

/**
 * Default ConfigBuilderInterface implementation.
 *
 * Merges TOML config files, $_ENV (string values only), and inline
 * overrides into a merged configuration array.
 *
 * Adapted from DGLab source (Core/Config/ConfigBuilder.php) with
 * TOML loading replacing PHP file loading per owner decision D3.
 *
 * @package SovereignStack\Core\Config
 */
final class ConfigBuilder implements ConfigBuilderInterface
{
    /** @var array<array-key, mixed> */
    private array $data = [];

    /** @var array<int, array{0: string, 1: mixed}> */
    private array $overrides = [];

    private bool $frozen = false;

    public function loadFile(string $path): static
    {
        $this->assertNotFrozen();

        if (!is_file($path) || !is_readable($path)) {
            throw ConfigurationFileException::forMissingFile($path);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw ConfigurationFileException::forMissingFile($path);
        }

        $parser = new TomlParser();
        $loaded = $parser->parse($contents, $path);

        $this->data = $this->mergeRecursive($this->data, $loaded);

        return $this;
    }

    public function withOverride(string $key, mixed $value): static
    {
        $this->assertNotFrozen();
        $this->assertValidKey($key);
        $this->overrides[] = [$key, $value];
        return $this;
    }

    public function build(): array
    {
        $this->assertNotFrozen();
        $this->frozen = true;

        $merged = $this->data;

        foreach ($_ENV as $envKey => $envValue) {
            if (!is_string($envValue) || $envValue === '') {
                continue;
            }
            $configKey = strtolower(str_replace('_', '.', $envKey));
            $this->setNested($merged, $configKey, $envValue);
        }

        foreach ($this->overrides as [$key, $value]) {
            $this->setNested($merged, $key, $value);
        }

        /** @var array<string, mixed> $merged */
        return $merged;
    }

    private function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw InvalidConfigurationException::forBuilderFrozen();
        }
    }

    private function assertValidKey(string $key): void
    {
        if ($key === '') {
            throw InvalidConfigurationException::forMalformedKey('', 'key cannot be empty');
        }
        if (str_contains($key, '..')) {
            throw InvalidConfigurationException::forMalformedKey(
                $key,
                'contains an empty segment (consecutive dots)',
            );
        }
    }

    /**
     * Recursively merge two config arrays.
     *
     * @param array<array-key, mixed> $left
     * @param array<array-key, mixed> $right
     *
     * @return array<array-key, mixed>
     */
    private function mergeRecursive(array $left, array $right): array
    {
        foreach ($right as $key => $value) {
            if (array_key_exists($key, $left) && is_array($left[$key]) && is_array($value)) {
                $left[$key] = $this->mergeRecursive($left[$key], $value);
            } else {
                $left[$key] = $value;
            }
        }
        return $left;
    }

    /**
     * Set a value at a dot-notation path, creating intermediate arrays.
     *
     * @param array<array-key, mixed> $array Reference — mutated in place.
     */
    private function setNested(array &$array, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &$array;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }
}
