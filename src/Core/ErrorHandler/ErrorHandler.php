<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\ErrorHandler;

use Psr\Log\LoggerInterface;
use Throwable;

final class ErrorHandler
{
    private ?LoggerInterface $logger = null;

    public function __construct(
        private readonly bool $displayErrors = false,
    ) {}

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function unregister(): void
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorName = $this->severityToString($severity);
        $logMessage = "{$errorName}: {$message} in {$file}:{$line}";

        $this->logger?->error($logMessage);

        if ($this->displayErrors) {
            fwrite(STDERR, $logMessage . "\n");
        }

        return true;
    }

    public function handleException(Throwable $exception): void
    {
        $logMessage = 'Uncaught ' . $exception::class . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
        $this->logger?->critical($logMessage, ['trace' => $exception->getTraceAsString()]);

        if ($this->displayErrors) {
            fwrite(STDERR, $logMessage . "\n");
            fwrite(STDERR, $exception->getTraceAsString() . "\n");
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    private function severityToString(int $severity): string
    {
        return match ($severity) {
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            default => 'Unknown',
        };
    }
}
