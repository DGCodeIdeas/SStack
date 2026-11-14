<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\EventDispatcher;

use Psr\EventDispatcher\ListenerProviderInterface;

final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<class-string, array<callable>> */
    private array $listeners = [];

    /**
     * @param class-string $eventType
     */
    public function addListener(string $eventType, callable $listener): void
    {
        if (!isset($this->listeners[$eventType])) {
            $this->listeners[$eventType] = [];
        }
        $this->listeners[$eventType][] = $listener;
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;

        foreach ($this->listeners as $type => $listeners) {
            if ($event instanceof $type || $eventClass === $type || is_subclass_of($event, $type)) {
                foreach ($listeners as $listener) {
                    yield $listener;
                }
            }
        }
    }
}
