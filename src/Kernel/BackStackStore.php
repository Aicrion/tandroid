<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Activity\BackStackEntry;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Persists each user's Activity back-stack between stateless
 * requests, using the framework's chained cache pool (Redis with
 * in-memory/array fallback — see Cache\CachePoolFactory).
 */
final class BackStackStore
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /** @return list<BackStackEntry> */
    public function all(int $chatId): array
    {
        $item = $this->cache->getItem($this->key($chatId));

        return $item->isHit() ? $item->get() : [];
    }

    public function push(int $chatId, BackStackEntry $entry, bool $clear = false): void
    {
        $stack = $clear ? [] : $this->all($chatId);
        $stack[] = $entry;

        $this->save($chatId, $stack);
    }

    public function pop(int $chatId): ?BackStackEntry
    {
        $stack = $this->all($chatId);
        $entry = array_pop($stack);

        $this->save($chatId, $stack);

        return $entry;
    }

    public function current(int $chatId): ?BackStackEntry
    {
        $stack = $this->all($chatId);
        $key = array_key_last($stack);

        return $key !== null ? ($stack[$key] ?? null) : null;
    }

    private function save(int $chatId, array $stack): void
    {
        $item = $this->cache->getItem($this->key($chatId));
        $item->set($stack);
        $this->cache->save($item);
    }

    private function key(int $chatId): string
    {
        return 'aicrion.backstack.' . $chatId;
    }
}