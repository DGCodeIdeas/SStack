<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

/**
 * Immutable configuration repository.
 *
 * Worker-scoped: a single instance is built at boot and reused for the
 * worker's lifetime. The backing array is readonly — no mutators exist
 * on the public interface.
 *
 * Security: all() returns a redacted view — keys matching SECRET_PATTERN
 * are replaced with '***REDACTED***'. Use allRaw() for the unredacted
 * tree (trusted internal callers only — NEVER log or expose to clients).
 *
 * @package SovereignStack\Core\Config
 */
interface ConfigInterface
{
    /**
     * Regex pattern for keys whose values must be redacted in all().
     * Matches: password, passwd, secret, token, authorization, cookie,
     * private_key, api_key, secret_key, and standalone 'key'.
     */
    public const SECRET_PATTERN = '/password|passwd|secret|token|authorization|cookie|private_key|api_key|secret_key|\bkey\b/i';

    /**
     * Retrieve a configuration value using dot-notation.
     *
     * Returns the RAW value — secrets are NOT redacted on direct lookup.
     *
     * @param string $key Dot-separated path, e.g. "app.name" or "db.host".
     * @param mixed $default Returned when the key is absent. Default null.
     *
     * @return mixed The stored value, or $default when absent.
     *
     * @throws Exception\InvalidConfigurationException If the key is empty or contains consecutive dots.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieve a configuration value, throwing when absent.
     *
     * Use for keys whose absence is a boot-time error.
     *
     * @param string $key Dot-separated path.
     *
     * @throws Exception\MissingConfigurationException When the key is absent.
     * @throws Exception\InvalidConfigurationException If the key is empty or contains consecutive dots.
     *
     * @return mixed The stored value. Never returns the default.
     */
    public function getOrFail(string $key): mixed;

    /**
     * Whether the key exists in the repository.
     *
     * @param string $key Dot-separated path.
     *
     * @throws Exception\InvalidConfigurationException If the key is empty or contains consecutive dots.
     */
    public function has(string $key): bool;

    /**
     * All configuration values as a nested array, with secrets REDACTED.
     *
     * Any key matching SECRET_PATTERN is replaced with '***REDACTED***'.
     * Safe to log, serialize, or expose to diagnostic endpoints.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * All configuration values as a nested array, WITHOUT redaction.
     *
     * Returns the raw configuration tree including passwords, tokens, and
     * secrets. ONLY call from trusted internal code. NEVER log the return
     * value or expose it to clients.
     *
     * @return array<string, mixed>
     */
    public function allRaw(): array;

    /**
     * The detected runtime environment.
     *
     * @throws Exception\InvalidEnvironmentException If environment was not set during construction.
     */
    public function getEnvironment(): Environment;
}
