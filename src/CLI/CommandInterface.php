<?php

declare(strict_types=1);

namespace SovereignStack\SStack\CLI;

interface CommandInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function configure(): void;

    public function execute(InputInterface $input, OutputInterface $output): int;
}
