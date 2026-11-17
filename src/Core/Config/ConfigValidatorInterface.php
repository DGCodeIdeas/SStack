<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

/**
 * Validates merged configuration against a PHP array schema.
 *
 * Per owner decision D6 + B2: PHP array-based validation schemas.
 *
 * @package SovereignStack\Core\Config
 */
interface ConfigValidatorInterface
{
    /**
     * Validate a merged configuration array against a schema.
     *
     * Schema structure:
     * [
     *     'required' => ['key' => 'type', ...],
     *     'optional' => ['key' => 'type', ...],
     *     'constraints' => ['key' => fn($v) => bool, ...],
     *     'required_secrets' => ['key', ...],
     * ]
     *
     * @param array<string, mixed> $data The merged configuration.
     * @param array<string, mixed> $schema The validation schema.
     *
     * @throws Exception\MissingConfigurationException When a required key or secret is absent.
     * @throws Exception\InvalidConfigurationException When a type mismatch or constraint violation occurs.
     */
    public function validate(array $data, array $schema): void;
}
