<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel;

interface BootstrapperInterface
{
    public function bootstrap(KernelInterface $kernel): void;
}
