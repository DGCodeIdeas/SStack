<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config;

/**
 * Loads environment variables from a .env file into $_ENV.
 *
 * Implementations MUST:
 *  - Parse KEY=VALUE lines, ignoring blank lines and # comments.
 *  - Honour surrounding quotes (single and double).
 *  - Support ${VAR} interpolation inside double-quoted and bare values.
 *  - NOT overwrite keys already present in $_ENV (env wins over .env file).
 *  - Write results to $_ENV only — never to getenv() (thread safety).
 *
 * @package SovereignStack\Core\Config
 */
interface EnvLoaderInterface
{
    /**
     * Load environment variables from a .env file.
     *
     * @param string $path Absolute path to the .env file.
     *
     * @throws Exception\ConfigurationFileException When the file is missing, unreadable, or malformed.
     *
     * @return array<string, string> The variables loaded (also written to $_ENV).
     */
    public function load(string $path): array;
}
