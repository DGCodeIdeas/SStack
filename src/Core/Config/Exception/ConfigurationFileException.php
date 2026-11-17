<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Exception;

/**
 * Thrown when a configuration file cannot be loaded: file missing,
 * file unreadable, TOML parse error, or .env parse error.
 *
 * Contains file path and line number (where determinable) for diagnostics.
 * NEVER includes secret values or file contents in the message.
 *
 * @package SovereignStack\Core\Config\Exception
 */
class ConfigurationFileException extends ConfigurationException
{
    public static function forMissingFile(string $path): self
    {
        return new self("Configuration file does not exist or is unreadable: {$path}");
    }

    public static function forParseError(string $path, int $lineNumber, string $error): self
    {
        return new self(
            "Parse error in {$path} at line {$lineNumber}: {$error}",
        );
    }

    public static function forDuplicateKey(string $path, int $lineNumber, string $key): self
    {
        return new self(
            "Duplicate key '{$key}' in {$path} at line {$lineNumber}.",
        );
    }

    public static function forMalformedLine(string $path, int $lineNumber, string $reason): self
    {
        return new self(
            "Malformed line {$lineNumber} in {$path}: {$reason}",
        );
    }
}
