<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

/**
 * Builds an immutable ConfigInterface from TOML config files,
 * environment variables, and inline overrides.
 *
 * Build order (later sources override earlier):
 *   1. TOML config files (parsed by native TomlParser)
 *   2. $_ENV variables (string values only, dot-converted)
 *   3. Inline overrides via withOverride()
 *
 * After build() is called, the builder is frozen — further mutations
 * throw. This enforces worker-scope discipline.
 *
 * @package SovereignStack\Core\Config
 */
interface ConfigBuilderInterface
{
    /**
     * Load and parse a TOML config file.
     *
     * @param string $path Absolute path to a TOML file.
     *
     * @throws Exception\ConfigurationFileException When the file is missing, unreadable, or contains invalid TOML.
     */
    public function loadFile(string $path): static;

    /**
     * Add an inline override.
     *
     * Applied after all file loads and $_ENV merge. Dot-notation supported.
     *
     * @param string $key Dot-separated path, e.g. "app.debug".
     * @param mixed $value Any value.
     *
     * @throws Exception\InvalidConfigurationException If the builder is frozen or the key is malformed.
     */
    public function withOverride(string $key, mixed $value): static;

    /**
     * Freeze the builder and produce the merged configuration array.
     *
     * After this call, further loadFile() / withOverride() calls throw.
     *
     * @return array<string, mixed> The merged configuration tree.
     *
     * @throws Exception\InvalidConfigurationException If build() is called twice.
     */
    public function build(): array;
}
