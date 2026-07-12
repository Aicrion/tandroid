# Caching and Redis

The framework uses a single, PSR-6-compatible cache layer
(`Psr\Cache\CacheItemPoolInterface`) throughout: `BackStackStore`
(navigation stack), `StateStore` (ViewModels), and tracking each
chat's "first seen" state for Broadcasts — all share the same pool.

## Automatic Fallback — No Manual Setup

`Cache\CachePoolFactory::create()` builds a
`Symfony\Component\Cache\Adapter\ChainAdapter`:

1. If `cache.redis_dsn` is set in the config, it attempts to connect
   to Redis.
2. If the DSN is empty **or** the connection fails, it silently
   (without throwing) falls back to only
   `Symfony\Component\Cache\Adapter\FilesystemAdapter` (the
   `var/cache` folder).

This means on shared hosting where Redis isn't available, just leave
`redis_dsn` empty or omit it entirely — the framework works with the
filesystem cache without any extra code on your part. On a VPS/Docker
setup with Redis available, just set the DSN:

```yaml
cache:
  redis_dsn: '%env(AICRION_REDIS_DSN)%'
```

```bash
export AICRION_REDIS_DSN="redis://127.0.0.1:6379"
```

## Why Redis Is Strongly Recommended for Polling

In webhook mode, every request is independent, so the filesystem
cache is fine. But if you run multiple `bin/poll.php` workers/
processes in parallel (or multiple servers behind a webhook load
balancer), you must use Redis so each user's navigation stack and
ViewModel stay shared and consistent across all workers — the
filesystem cache is local to each process.

## Using the Cache Directly in Your Own Plugins

Any service that type-hints `Psr\Cache\CacheItemPoolInterface` in
its constructor automatically receives the same shared pool from the
DI Container:

```php
final class RateLimiter
{
    public function __construct(
        private readonly \Psr\Cache\CacheItemPoolInterface $cache,
    ) {}

    public function tooManyAttempts(int $userId): bool
    {
        $item = $this->cache->getItem("ratelimit.$userId");

        return $item->isHit() && $item->get() >= 5;
    }
}
```

## Internal Framework Keys (for Debugging)

| Key prefix | Used by |
|---|---|
| `aicrion.backstack.{chat_id}` | `Kernel\BackStackStore` |
| `aicrion.viewmodel.{ViewModel_FQCN}.{chat_id}` | `Kernel\ViewModel\StateStore` |
| `aicrion.seen_chat.{chat_id}` | `Kernel\Kernel` (detects a chat's first-seen state for `UserJoinedEvent`) |

If, during development, you need to reset a specific user's state,
just delete these keys from Redis/`var/cache`.
