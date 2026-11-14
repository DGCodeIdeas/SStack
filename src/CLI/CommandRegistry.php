<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

use SovereignStack\SStack\Core\Container\ContainerInterface;

final class CommandRegistry
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function register(CommandInterface $command): void
    {
        $command->configure();
        $this->commands[$command->getName()] = $command;
    }

    public function registerClass(string $commandClass): void
    {
        if ($this->container->has($commandClass)) {
            $command = $this->container->get($commandClass);
        } else {
            $command = new $commandClass();
        }
        $this->register($command);
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function get(string $name): ?CommandInterface
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * @return array<string, CommandInterface>
     */
    public function all(): array
    {
        return $this->commands;
    }
}
