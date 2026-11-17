<?php

declare(strict_types=1);

namespace SovereignStack\Core\Config\Exception;

/**
 * Base exception for all configuration errors.
 *
 * All CORE-10 exceptions extend this class, allowing callers to catch
 * any configuration-related error with a single catch clause.
 *
 * @package SovereignStack\Core\Config\Exception
 */
class ConfigurationException extends \RuntimeException
{
}
