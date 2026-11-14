<?php

declare(strict_types=1);

namespace SovereignStack\SStack\Core\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;

final class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable|string> */
    private array $factories = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    /** @var array<string, array<string>> */
    private array $tags = [];

    /** @var array<string, array<string>> */
    private array $tagged = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    public function set(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
        unset($this->factories[$id], $this->singletons[$id]);
    }

    public function singleton(string $id, callable|string $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = true;
    }

    public function factory(string $id, callable|string $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = false;
    }

    public function tag(string $id, string $tag): void
    {
        if (!isset($this->tags[$id])) {
            $this->tags[$id] = [];
        }
        if (!in_array($tag, $this->tags[$id], true)) {
            $this->tags[$id][] = $tag;
        }
        if (!isset($this->tagged[$tag])) {
            $this->tagged[$tag] = [];
        }
        if (!in_array($id, $this->tagged[$tag], true)) {
            $this->tagged[$tag][] = $id;
        }
    }

    public function tagged(string $tag): array
    {
        return $this->tagged[$tag] ?? [];
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            if (class_exists($id)) {
                return $this->autowire($id);
            }
            throw new NotFoundException("Service not found: {$id}");
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException("Circular dependency detected: {$id}");
        }

        $this->resolving[$id] = true;

        $factory = $this->factories[$id];
        $instance = is_callable($factory)
            ? ($factory)($this)
            : $this->autowire($factory);

        unset($this->resolving[$id]);

        if ($this->singletons[$id] ?? false) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]) || class_exists($id);
    }

    private function autowire(string $class): object
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot autowire {$class}: {$e->getMessage()}", 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type === null) {
                if ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                    continue;
                }
                throw new ContainerException("Cannot resolve parameter \${$param->getName()} of {$class}: no type hint");
            }

            if ($type instanceof ReflectionUnionType) {
                $resolved = false;
                foreach ($type->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType && !$unionType->isBuiltin()) {
                        $params[] = $this->get($unionType->getName());
                        $resolved = true;
                        break;
                    }
                }
                if (!$resolved) {
                    if ($param->isDefaultValueAvailable()) {
                        $params[] = $param->getDefaultValue();
                        continue;
                    }
                    throw new ContainerException("Cannot resolve union type parameter \${$param->getName()} of {$class}");
                }
                continue;
            }

            if (!$type instanceof ReflectionNamedType) {
                throw new ContainerException("Unsupported type for parameter \${$param->getName()} of {$class}");
            }

            $typeName = $type->getName();

            if ($type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                    continue;
                }
                throw new ContainerException("Cannot resolve built-in type {$typeName} for parameter \${$param->getName()} of {$class}");
            }

            $params[] = $this->get($typeName);
        }

        return $reflection->newInstanceArgs($params);
    }
}
