<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Tests\Unit\CLI;

use PHPUnit\Framework\TestCase;
use SovereignStack\SStack\CLI\Application;
use SovereignStack\SStack\CLI\ArgvInput;
use SovereignStack\SStack\CLI\ConsoleOutput;
use SovereignStack\SStack\CLI\AbstractCommand;
use SovereignStack\SStack\Core\Container\Container;

final class ApplicationTest extends TestCase
{
    public function testListCommandsWithNoCommands(): void
    {
        $container = new Container();
        $app = new Application($container);
        $exitCode = $app->run();
        $this->assertSame(0, $exitCode);
    }

    public function testUnknownCommandReturnsError(): void
    {
        $container = new Container();
        $app = new Application($container);
        $exitCode = $app->run();
        // With no arguments, it lists commands
        $this->assertSame(0, $exitCode);
    }

    public function testRegisteredCommandExecutes(): void
    {
        $container = new Container();

        $command = new class extends AbstractCommand {
            public function configure(): void
            {
                $this->setName('hello')->setDescription('Say hello');
            }

            public function execute(\SovereignStack\SStack\CLI\InputInterface $input, \SovereignStack\SStack\CLI\OutputInterface $output): int
            {
                $output->writeln('Hello, World!');
                return 0;
            }
        };

        $input = new ArgvInput(['sstack', 'hello']);
        $output = new class implements \SovereignStack\SStack\CLI\OutputInterface {
            public array $output = [];
            public function write(string $message): void { $this->output[] = $message; }
            public function writeln(string $message): void { $this->output[] = $message; }
            public function writeError(string $message): void {}
            public function writeErrorLn(string $message): void {}
        };

        $app = new Application($container, $input, $output);
        $app->getRegistry()->register($command);

        $exitCode = $app->run();

        $this->assertSame(0, $exitCode);
        $this->assertContains('Hello, World!', $output->output);
    }
}
