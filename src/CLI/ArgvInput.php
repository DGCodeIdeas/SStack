<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

final class ArgvInput implements InputInterface
{
    /** @var array<int, string> */
    private array $arguments;

    /** @var array<string, string|null> */
    private array $options;

    public function __construct(array $argv)
    {
        // Remove script name
        array_shift($argv);

        $this->arguments = [];
        $this->options = [];

        foreach ($argv as $token) {
            if (str_starts_with($token, '--')) {
                $optionPart = substr($token, 2);
                if (str_contains($optionPart, '=')) {
                    [$name, $value] = explode('=', $optionPart, 2);
                    $this->options[$name] = $value;
                } else {
                    $this->options[$optionPart] = null;
                }
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    public function getFirstArgument(): ?string
    {
        return $this->arguments[0] ?? null;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(int|string $position): ?string
    {
        if (is_int($position)) {
            return $this->arguments[$position] ?? null;
        }
        return null;
    }

    public function getOption(string $name): ?string
    {
        if (!$this->hasOption($name)) {
            return null;
        }
        $value = $this->options[$name];
        return $value ?? '';
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function isInteractive(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDIN);
    }
}
