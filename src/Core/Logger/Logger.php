<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

final class Logger extends AbstractLogger
{
    public function __construct(
        private readonly string $channel = 'app',
    ) {}

    /**
     * @param mixed[] $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
        $channel = $this->channel;
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        fwrite(STDERR, "[{$timestamp}] {$channel}.{$level}: {$message}{$contextStr}\n");
    }
}
