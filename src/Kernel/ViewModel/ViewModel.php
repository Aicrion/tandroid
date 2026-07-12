<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel\ViewModel;

/**
 * Base class for state holders that outlive a single Activity
 * instance, mirroring androidx.lifecycle.ViewModel. While a
 * BotActivity is recreated on every request (stateless PHP), its
 * ViewModel is rehydrated from the StateStore by scope key (usually
 * chatId), so data like a shopping cart or wizard progress survives
 * navigation between Activities without leaking into the Activity's
 * own transient properties.
 */
abstract class ViewModel
{
    /** @var array<string, mixed> */
    protected array $state = [];

    final public function hydrate(array $state): void
    {
        $this->state = $state;
    }

    final public function dehydrate(): array
    {
        return $this->state;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    protected function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }
}