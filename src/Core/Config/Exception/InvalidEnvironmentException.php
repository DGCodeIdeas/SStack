<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Exception;

/**
 * Thrown when the runtime environment (APP_ENV) is absent or
 * contains an unrecognised value.
 *
 * Per owner decision D5: unknown values MUST produce an error.
 * This is stricter than the DGLab blueprint which defaulted to Production.
 *
 * @package SovereignStack\Core\Config\Exception
 */
class InvalidEnvironmentException extends ConfigurationException
{
    public static function forAbsentEnv(): self
    {
        return new self(
            'APP_ENV is not set. The environment must be explicitly specified '
            . '(development, staging, or production).',
        );
    }

    public static function forUnrecognisedValue(string $value): self
    {
        return new self(
            "APP_ENV value '{$value}' is not recognised. "
            . 'Valid values: development, dev, local, staging, stage, production, prod.',
        );
    }
}
