<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Exception;

/**
 * Thrown for configuration invalidity: type mismatches, constraint
 * violations, malformed keys, frozen builder mutations, and undefined
 * interpolations.
 *
 * @package SovereignStack\Core\Config\Exception
 */
class InvalidConfigurationException extends ConfigurationException
{
    public static function forKeyTypeMismatch(string $key, string $expectedType, string $actualType): self
    {
        return new self(
            "Configuration key '{$key}' expected type {$expectedType}, got {$actualType}.",
        );
    }

    public static function forKeyConstraintViolation(string $key): self
    {
        return new self(
            "Configuration key '{$key}' failed constraint validation.",
        );
    }

    public static function forMalformedKey(string $key, string $reason): self
    {
        return new self(
            "Configuration key '{$key}' is malformed: {$reason}",
        );
    }

    public static function forBuilderFrozen(): self
    {
        return new self(
            'ConfigBuilder is frozen after build(). Create a new builder to produce another repository.',
        );
    }

    public static function forUndefinedInterpolation(string $varName, string $context): self
    {
        return new self(
            "Interpolation references undefined variable '\${$varName}' in: {$context}",
        );
    }
}
