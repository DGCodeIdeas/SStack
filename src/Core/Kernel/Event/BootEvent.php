<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel\Event;

use SovereignStack\SStack\Core\Kernel\KernelInterface;

final class BootEvent extends KernelEvent
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);
    }
}
