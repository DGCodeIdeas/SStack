<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

interface InputInterface
{
    public function getFirstArgument(): ?string;

    /**
     * @return array<int, string>
     */
    public function getArguments(): array;

    public function getArgument(string $name): ?string;

    public function getOption(string $name): ?string;

    public function hasOption(string $name): bool;

    public function isInteractive(): bool;
}
