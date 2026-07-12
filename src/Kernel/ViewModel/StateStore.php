<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel\ViewModel;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Persists ViewModel state keyed by (scopeId, ViewModel FQCN),
 * backed by the framework's chained cache pool (Redis + filesystem
 * fallback). scopeId is typically the chat_id, so a ViewModel scoped
 * to a conversation survives across many Activities on the
 * back-stack — exactly like a ViewModel surviving Activity
 * recreation on Android configuration changes.
 */
final class StateStore
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * @template T of ViewModel
     * @param class-string<T> $viewModelClass
     * @return T
     */
    public function resolve(string $viewModelClass, int $scopeId): ViewModel
    {
        /** @var T $instance */
        $instance = new $viewModelClass();
        $item = $this->cache->getItem($this->key($viewModelClass, $scopeId));

        if ($item->isHit()) {
            $instance->hydrate($item->get());
        }

        return $instance;
    }

    public function persist(string $viewModelClass, int $scopeId, ViewModel $viewModel): void
    {
        $item = $this->cache->getItem($this->key($viewModelClass, $scopeId));
        $item->set($viewModel->dehydrate());
        $this->cache->save($item);
    }

    public function clear(string $viewModelClass, int $scopeId): void
    {
        $this->cache->deleteItem($this->key($viewModelClass, $scopeId));
    }

    private function key(string $viewModelClass, int $scopeId): string
    {
        return 'aicrion.viewmodel.' . str_replace('\\', '_', $viewModelClass) . '.' . $scopeId;
    }
}