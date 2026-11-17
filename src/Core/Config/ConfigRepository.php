<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

use SovereignStack\Core\Config\Exception\InvalidConfigurationException;
use SovereignStack\Core\Config\Exception\MissingConfigurationException;

/**
 * Immutable configuration repository backed by a nested array.
 *
 * Built once at boot, reused for the worker's lifetime. The backing
 * array is private readonly — no mutator exists on the public interface.
 *
 * Performance target: resolution of a nested key < 10µs (0.01ms).
 * Achieved via simple array traversal with no reflection or caching.
 *
 * @package SovereignStack\Core\Config
 */
final class ConfigRepository implements ConfigInterface
{
    private ?Environment $environment = null;

    /**
     * @param array<string, mixed> $data Frozen, merged configuration tree.
     */
    public function __construct(
        private readonly array $data = [],
    ) {
    }

    /**
     * Set the detected environment. Called by ConfigLoader after
     * construction. Not part of ConfigInterface — internal method.
     *
     * @internal
     */
    public function withEnvironment(Environment $environment): self
    {
        $clone = clone $this;
        $clone->environment = $environment;
        return $clone;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = $this->segments($key);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function getOrFail(string $key): mixed
    {
        $segments = $this->segments($key);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                throw MissingConfigurationException::forKey($key);
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function has(string $key): bool
    {
        $segments = $this->segments($key);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    public function all(): array
    {
        return $this->redact($this->data);
    }

    public function allRaw(): array
    {
        return $this->data;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment ?? throw MissingConfigurationException::forKey('app.env');
    }

    /**
     * Recursively redact sensitive keys from a configuration array.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function redact(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(ConfigInterface::SECRET_PATTERN, $key) === 1) {
                $result[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Split a dot-notation key into traversal segments.
     *
     * @return non-empty-list<string>
     *
     * @throws InvalidConfigurationException When the key is empty or contains an empty segment.
     */
    private function segments(string $key): array
    {
        if ($key === '') {
            throw InvalidConfigurationException::forMalformedKey('', 'key cannot be empty');
        }

        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if ($segment === '') {
                throw InvalidConfigurationException::forMalformedKey(
                    $key,
                    'contains an empty segment (consecutive dots)',
                );
            }
        }

        return $segments;
    }
}
