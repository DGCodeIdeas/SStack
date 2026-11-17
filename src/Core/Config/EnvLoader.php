<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

use SovereignStack\Core\Config\Exception\ConfigurationFileException;

/**
 * Parses .env files into $_ENV.
 *
 * Faithfully reconstructed from DGLab source at baseline
 * 543329ed84a2148308e0cc455ac7e2fe27cf35f4 (Core/Config/EnvLoader.php).
 *
 * Spec:
 *  - Lines starting with # (after optional whitespace) are comments.
 *  - Blank lines are skipped.
 *  - export KEY=VALUE and KEY=VALUE are both accepted.
 *  - Single-quoted values: literal (no interpolation).
 *  - Double-quoted values: ${VAR} interpolation against $_ENV.
 *  - Bare values: trimmed; ${VAR} interpolation applies.
 *  - Existing $_ENV keys are NEVER overwritten — environment wins over .env file.
 *  - Writes to $_ENV only — never calls putenv() or getenv() (thread safety).
 *
 * @package SovereignStack\Core\Config
 */
final class EnvLoader implements EnvLoaderInterface
{
    public function load(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw ConfigurationFileException::forMissingFile($path);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw ConfigurationFileException::forMissingFile($path);
        }

        $loaded = [];

        foreach ($this->lines($contents, $path) as [$lineNum, $key, $value]) {
            if (array_key_exists($key, $_ENV)) {
                continue;
            }
            $resolved = $this->interpolate($value);
            $_ENV[$key] = $resolved;
            $loaded[$key] = $resolved;
        }

        return $loaded;
    }

    /**
     * Yield [lineNum, key, value] tuples from the file contents.
     *
     * @return iterable<array{0: int, 1: string, 2: string}>
     */
    private function lines(string $contents, string $path): iterable
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        if ($lines === false) {
            $lines = [];
        }

        foreach ($lines as $lineNum => $line) {
            $trimmed = ltrim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = ltrim(substr($trimmed, 7));
            }

            $equalsPos = strpos($trimmed, '=');
            if ($equalsPos === false) {
                throw ConfigurationFileException::forMalformedLine(
                    $path,
                    $lineNum + 1,
                    "missing '=' in '{$trimmed}'",
                );
            }

            $key = trim(substr($trimmed, 0, $equalsPos));
            $rawValue = trim(substr($trimmed, $equalsPos + 1));

            if ($key === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key)) {
                throw ConfigurationFileException::forMalformedLine(
                    $path,
                    $lineNum + 1,
                    "invalid key '{$key}'. Keys must match /^[A-Z_][A-Z0-9_]*$/i",
                );
            }

            yield [$lineNum + 1, $key, $this->unwrapQuotes($rawValue)];
        }
    }

    /**
     * Strip surrounding quotes from a value.
     * Single quotes: no interpolation (literal).
     * Double quotes: interpolation applies (return inner content).
     * Bare: return as-is.
     */
    private function unwrapQuotes(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if ($first === "'" && $last === "'") {
                return substr($value, 1, -1);
            }

            if ($first === '"' && $last === '"') {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    /**
     * Resolve ${VAR} references against $_ENV.
     * Undefined references are left as literal ${VAR}.
     */
    private function interpolate(string $value): string
    {
        return preg_replace_callback(
            '/\$\{([A-Z_][A-Z0-9_]*)\}/i',
            fn (array $m): string => $this->resolveEnvVar($m[1], $m[0]),
            $value,
        ) ?? $value;
    }

    /**
     * Look up an environment variable, returning $fallback if unset.
     */
    private function resolveEnvVar(string $varName, string $fallback): string
    {
        if (!array_key_exists($varName, $_ENV)) {
            return $fallback;
        }

        $value = $_ENV[$varName];
        return is_string($value) ? $value : $fallback;
    }
}
