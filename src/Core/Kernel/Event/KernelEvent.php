<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel\Event;

use SovereignStack\SStack\Core\Kernel\KernelInterface;

abstract class KernelEvent
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {}

    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }
}
