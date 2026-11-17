<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

/**
 * ConfigLoader facade — orchestrates the complete configuration lifecycle.
 *
 * Per owner decision D1 + B3: single public load() method that delegates
 * to ConfigBuilder, EnvLoader, and ConfigValidator.
 *
 * Lifecycle:
 *   1. Load TOML config files (via ConfigBuilder)
 *   2. Load .env file (via EnvLoader)
 *   3. Merge $_ENV into config (via ConfigBuilder::build())
 *   4. Apply explicit overrides (via ConfigBuilder)
 *   5. Detect environment (from merged config's app.env key)
 *   6. Validate against schema (via ConfigValidator)
 *   7. Construct frozen ConfigRepository
 *   8. Return ConfigInterface
 *
 * @package SovereignStack\Core\Config
 */
final class ConfigLoader
{
    /**
     * Orchestrates the complete configuration lifecycle.
     *
     * @param list<string> $configFiles TOML file paths in load order.
     * @param string|null $envFile .env file path (null = skip .env loading).
     * @param array<string, mixed> $overrides Explicit overrides (dot-notation keys).
     * @param array<string, mixed>|null $schema Validation schema (null = skip validation).
     *
     * @throws Exception\ConfigurationFileException If any file is missing or unparseable.
     * @throws Exception\InvalidEnvironmentException If APP_ENV is absent or unrecognised.
     * @throws Exception\MissingConfigurationException If a required key or secret is absent.
     * @throws Exception\InvalidConfigurationException If a type mismatch or constraint violation occurs.
     */
    public function load(
        array $configFiles = [],
        ?string $envFile = null,
        array $overrides = [],
        ?array $schema = null,
    ): ConfigInterface {
        // Step 1-3: Load TOML files and merge with $_ENV
        $builder = new ConfigBuilder();

        foreach ($configFiles as $path) {
            $builder->loadFile($path);
        }

        // Step 2: Load .env file (writes to $_ENV, env-wins-over-file)
        if ($envFile !== null) {
            $envLoader = new EnvLoader();
            $envLoader->load($envFile);
        }

        // Step 4: Apply explicit overrides
        foreach ($overrides as $key => $value) {
            $builder->withOverride($key, $value);
        }

        // Step 3: Merge everything (files → $_ENV → overrides)
        $merged = $builder->build();

        // Step 5: Detect environment
        $rawEnv = $this->getNested($merged, 'app.env');
        $environment = Environment::detect($rawEnv);

        // Step 6: Validate against schema
        if ($schema !== null) {
            $validator = new ConfigValidator();
            $validator->validate($merged, $schema);
        }

        // Step 7: Construct frozen ConfigRepository
        $repository = new ConfigRepository($merged);
        $repository = $repository->withEnvironment($environment);

        // Step 8: Return immutable ConfigInterface
        return $repository;
    }

    /**
     * Get a nested value from a config array using dot-notation.
     *
     * @param array<string, mixed> $data
     */
    private function getNested(array $data, string $key): ?string
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
}
