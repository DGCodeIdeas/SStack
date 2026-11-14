<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

interface OutputInterface
{
    public function write(string $message): void;

    public function writeln(string $message): void;

    public function writeError(string $message): void;

    public function writeErrorLn(string $message): void;
}
