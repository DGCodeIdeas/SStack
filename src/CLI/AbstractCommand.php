<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

abstract class AbstractCommand implements CommandInterface
{
    protected string $name;
    protected string $description = '';

    /** @var array<string, array{description: string, required: bool, default: ?string}> */
    protected array $arguments = [];

    /** @var array<string, array{description: string, short: ?string, default: ?string}> */
    protected array $options = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    protected function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    protected function addArgument(string $name, string $description = '', bool $required = false, ?string $default = null): static
    {
        $this->arguments[$name] = ['description' => $description, 'required' => $required, 'default' => $default];
        return $this;
    }

    protected function addOption(string $name, string $description = '', ?string $short = null, ?string $default = null): static
    {
        $this->options[$name] = ['description' => $description, 'short' => $short, 'default' => $default];
        return $this;
    }

    public function configure(): void
    {
        // Override in subclasses to set name, description, arguments, options
    }
}
