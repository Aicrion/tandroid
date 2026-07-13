<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Remembers the message_id of the last message the bot sent to a
 * given chat, so Kernel can optionally delete it before sending the
 * next one (see View::deletePreviousMessage() and the "Deleting the
 * Previous Message" section in docs/guide/views-and-widgets.md).
 *
 * This is intentionally separate from BackStackStore: the back-stack
 * tracks *which Activity* is active, while this tracks *which
 * Telegram message* currently represents that Activity on screen —
 * the two overlap conceptually but aren't the same lifecycle (e.g.
 * an in-place edit via IntentFlag::ReplaceMessage keeps the same
 * message_id across several back-stack pushes).
 *
 * No TTL, matching BackStackStore/ReplyActionStore: a chat can stay
 * on the same message indefinitely, and there's no safe fixed expiry
 * to apply without risking a legitimate delete being silently skipped.
 */
final class LastMessageStore
{
    private const string CACHE_KEY_PREFIX = 'aicrion.lastmsg.';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function remember(int $chatId, int $messageId): void
    {
        $item = $this->cache->getItem($this->key($chatId));
        $item->set($messageId);
        $this->cache->save($item);
    }

    public function get(int $chatId): ?int
    {
        $item = $this->cache->getItem($this->key($chatId));

        return $item->isHit() ? $item->get() : null;
    }

    public function forget(int $chatId): void
    {
        $this->cache->deleteItem($this->key($chatId));
    }

    private function key(int $chatId): string
    {
        return self::CACHE_KEY_PREFIX . $chatId;
    }
}
