<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Exception;

/**
 * Thrown when a required configuration key is absent (via getOrFail())
 * or when a required secret is missing or empty (via ConfigValidator).
 *
 * @package SovereignStack\Core\Config\Exception
 */
class MissingConfigurationException extends ConfigurationException
{
    public static function forKey(string $key): self
    {
        return new self(
            "Required configuration key '{$key}' is missing. "
            . 'Either set it in a config file, in $_ENV, or via ConfigBuilder::withOverride().',
        );
    }

    public static function forSecret(string $key): self
    {
        return new self(
            "Required secret configuration key '{$key}' is missing or empty.",
        );
    }
}
