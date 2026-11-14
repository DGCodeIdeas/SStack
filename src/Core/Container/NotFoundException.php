<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Container;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
