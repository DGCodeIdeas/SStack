<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
