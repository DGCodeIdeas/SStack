<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

/**
 * Runtime environment detected from APP_ENV.
 *
 * Per owner decision D5: native backed enum with Development, Staging,
 * and Production cases. Unknown or absent values MUST produce an error
 * (InvalidEnvironmentException).
 *
 * This is stricter than the DGLab blueprint which defaulted absent/
 * unrecognised values to Production (fail-safe).
 *
 * @package SovereignStack\Core\Config
 */
enum Environment: string
{
    case Development = 'development';
    case Staging = 'staging';
    case Production = 'production';

    /**
     * Detect environment from a raw APP_ENV string value.
     *
     * @param string|null $rawValue The raw APP_ENV value (may be null if absent).
     *
     * @throws Exception\InvalidEnvironmentException If value is null or unrecognised.
     */
    public static function detect(?string $rawValue): self
    {
        if ($rawValue === null) {
            throw Exception\InvalidEnvironmentException::forAbsentEnv();
        }

        $lower = strtolower(trim($rawValue));

        return match (true) {
            in_array($lower, ['development', 'dev', 'local'], true) => self::Development,
            in_array($lower, ['staging', 'stage'], true) => self::Staging,
            in_array($lower, ['production', 'prod'], true) => self::Production,
            default => throw Exception\InvalidEnvironmentException::forUnrecognisedValue($rawValue),
        };
    }
}
