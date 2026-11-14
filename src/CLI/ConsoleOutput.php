<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

final class ConsoleOutput implements OutputInterface
{
    public function write(string $message): void
    {
        fwrite(STDOUT, $message);
    }

    public function writeln(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    public function writeError(string $message): void
    {
        fwrite(STDERR, $message);
    }

    public function writeErrorLn(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
