<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Cache;

use Aicrion\Tandroid\Config\FrameworkConfig;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Builds the framework's cache pool with automatic graceful
 * degradation: Redis is preferred (shared state across workers,
 * required for multi-process polling), but on shared hosting where
 * Redis is unavailable, it transparently falls back to a filesystem
 * adapter chain — no code in the rest of the framework needs to know.
 */
final class CachePoolFactory
{
    public static function create(FrameworkConfig $config): CacheItemPoolInterface
    {
        $adapters = [];

        if ($config->redisDsn !== null && $config->redisDsn !== '') {
            try {
                $redis = RedisAdapter::createConnection($config->redisDsn);
                $adapters[] = new RedisAdapter($redis);
            } catch (\Throwable) {
                // Redis unreachable — silently degrade to filesystem only.
            }
        }

        $adapters[] = new FilesystemAdapter(namespace: 'aicrion', directory: 'var/cache');

        return new ChainAdapter($adapters);
    }
}