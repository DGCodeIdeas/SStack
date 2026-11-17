<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

use SovereignStack\Core\Config\Exception\InvalidConfigurationException;
use SovereignStack\Core\Config\Exception\MissingConfigurationException;

/**
 * Native configuration validator.
 *
 * Validates merged configuration against a PHP array schema.
 * Supports: required keys, optional keys, type checking, custom constraints,
 * and required secret availability.
 *
 * Per owner decision D6 + B2.
 *
 * @package SovereignStack\Core\Config
 */
final class ConfigValidator implements ConfigValidatorInterface
{
    public function validate(array $data, array $schema): void
    {
        $this->validateRequired($data, $schema['required'] ?? []);
        $this->validateOptional($data, $schema['optional'] ?? []);
        $this->validateConstraints($data, $schema['constraints'] ?? []);
        $this->validateRequiredSecrets($data, $schema['required_secrets'] ?? []);
    }

    /**
     * @param array<string, string> $required
     */
    private function validateRequired(array $data, array $required): void
    {
        foreach ($required as $key => $expectedType) {
            $value = $this->getNested($data, $key);

            if ($value === null && !$this->hasNested($data, $key)) {
                throw MissingConfigurationException::forKey($key);
            }

            $this->checkType($key, $value, $expectedType);
        }
    }

    /**
     * @param array<string, string> $optional
     */
    private function validateOptional(array $data, array $optional): void
    {
        foreach ($optional as $key => $expectedType) {
            if ($this->hasNested($data, $key)) {
                $this->checkType($key, $this->getNested($data, $key), $expectedType);
            }
        }
    }

    /**
     * @param array<string, callable> $constraints
     */
    private function validateConstraints(array $data, array $constraints): void
    {
        foreach ($constraints as $key => $closure) {
            $value = $this->getNested($data, $key);

            if ($value === null && !$this->hasNested($data, $key)) {
                continue;
            }

            if (!$closure($value)) {
                throw InvalidConfigurationException::forKeyConstraintViolation($key);
            }
        }
    }

    /**
     * @param list<string> $secrets
     */
    private function validateRequiredSecrets(array $data, array $secrets): void
    {
        foreach ($secrets as $key) {
            $value = $this->getNested($data, $key);

            if ($value === null || $value === '' || $value === []) {
                throw MissingConfigurationException::forSecret($key);
            }
        }
    }

    private function checkType(string $key, mixed $value, string $expectedType): void
    {
        $actualType = $this->getTypeName($value);

        $valid = match ($expectedType) {
            'string' => is_string($value),
            'int' => is_int($value),
            'float' => is_float($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'datetime' => $value instanceof \DateTimeImmutable,
            default => throw InvalidConfigurationException::forMalformedKey(
                $expectedType,
                "unknown type '{$expectedType}' in schema",
            ),
        };

        if (!$valid) {
            throw InvalidConfigurationException::forKeyTypeMismatch($key, $expectedType, $actualType);
        }
    }

    private function getTypeName(mixed $value): string
    {
        return match (true) {
            is_string($value) => 'string',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            is_array($value) => 'array',
            $value instanceof \DateTimeImmutable => 'datetime',
            $value === null => 'null',
            default => get_debug_type($value),
        };
    }

    private function getNested(array $data, string $key): mixed
    {
        $segments = explode('.', $key);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private function hasNested(array $data, string $key): bool
    {
        $segments = explode('.', $key);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }
}
