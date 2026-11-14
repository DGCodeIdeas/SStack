<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

use SovereignStack\SStack\Core\Container\ContainerInterface;
use Throwable;

final class Application
{
    private CommandRegistry $registry;
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(
        ContainerInterface $container,
        ?InputInterface $input = null,
        ?OutputInterface $output = null,
    ) {
        $this->registry = new CommandRegistry($container);
        $this->input = $input ?? new ArgvInput($_SERVER['argv'] ?? []);
        $this->output = $output ?? new ConsoleOutput();
    }

    public function getRegistry(): CommandRegistry
    {
        return $this->registry;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function run(): int
    {
        $commandName = $this->input->getFirstArgument();

        if ($commandName === null || $commandName === 'help' || $commandName === 'list') {
            return $this->listCommands();
        }

        if (!$this->registry->has($commandName)) {
            $this->output->writeErrorLn("Command not found: {$commandName}");
            $this->output->writeErrorLn('');
            $this->listCommands();
            return 1;
        }

        $command = $this->registry->get($commandName);

        try {
            return $command->execute($this->input, $this->output);
        } catch (Throwable $e) {
            $this->output->writeErrorLn("Error: {$e->getMessage()}");
            return 1;
        }
    }

    private function listCommands(): int
    {
        $this->output->writeln('SovereignStack CLI');
        $this->output->writeln('');
        $this->output->writeln('Available commands:');

        $commands = $this->registry->all();
        if (empty($commands)) {
            $this->output->writeln('  (none registered)');
            return 0;
        }

        $maxNameLength = max(array_map(fn($n) => strlen($n), array_keys($commands)));

        foreach ($commands as $name => $command) {
            $padded = str_pad($name, $maxNameLength + 4);
            $desc = $command->getDescription();
            $this->output->writeln("  {$padded}{$desc}");
        }

        return 0;
    }
}
