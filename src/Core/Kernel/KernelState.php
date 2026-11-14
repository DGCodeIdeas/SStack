<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Kernel;

enum KernelState: string
{
    case Unbooted = 'unbooted';
    case Booting = 'booting';
    case Booted = 'booted';
    case Handling = 'handling';
    case Terminated = 'terminated';
    case Failed = 'failed';
}
