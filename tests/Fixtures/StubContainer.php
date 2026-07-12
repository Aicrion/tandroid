<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Fixtures;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Minimal PSR-11 container used only by tests: resolves any
 * zero-argument class by instantiating it directly, and caches the
 * instance for the lifetime of the container (same contract as a
 * compiled Symfony service container returning shared services).
 */
final class StubContainer implements ContainerInterface
{
    /** @var array<string, object> */
    private array $instances = [];

    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new class ("Service \"{$id}\" not found.") extends \RuntimeException implements NotFoundExceptionInterface {};
        }

        return $this->instances[$id] ??= new $id();
    }

    public function has(string $id): bool
    {
        return class_exists($id);
    }
}