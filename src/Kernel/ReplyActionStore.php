<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Makes `Button::action()`/`Button::actionReplace()` work inside a
 * Reply keyboard (`Keyboard::reply()`), not just an Inline one.
 *
 * Telegram's Reply keyboards have no `callback_data` — tapping a
 * Reply button simply makes the client send a normal text message
 * whose content is the button's label, exactly as if the user had
 * typed it. There is no per-tap token Telegram hands back the way
 * it does for an inline callback_query, so an explicit-intent Reply
 * button can only be resolved by remembering, server-side, which
 * Activity+payload each currently-visible label maps to.
 *
 * That mapping is inherently per-chat: two different chats can be
 * looking at two different Reply keyboards at the same time (e.g.
 * one on StartActivity, another mid-way through a wizard), and the
 * same label text ("⬅️ بازگشت") may legitimately point to different
 * targets in each. So, unlike CallbackDataStore (whose token is
 * self-contained and safe to resolve globally), this store is keyed
 * by chat_id and always holds the *entire* action map for whichever
 * Reply keyboard was most recently sent to that chat — Kernel
 * overwrites it every time a new Reply keyboard is delivered (see
 * Kernel::deliver()), so stale labels from a keyboard that has since
 * been replaced are never resolvable.
 *
 * Deliberately has no TTL, mirroring BackStackStore: a Reply
 * keyboard stays visible and tappable in the Telegram client
 * indefinitely, until the bot sends a new one (or a
 * ReplyKeyboardRemove), so there is no safe fixed expiry to apply
 * here without risking a live, on-screen button silently breaking.
 */
final class ReplyActionStore
{
    private const string CACHE_KEY_PREFIX = 'aicrion.replyactions.';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * Replaces the entire action map for $chatId. Called once per
     * outgoing Reply keyboard — including with an empty array when
     * the new keyboard has no explicit-intent buttons at all — so a
     * label that belonged to a previous keyboard can never be
     * resolved against the new one.
     *
     * @param array<string, array{a: string, p: array, f: list<string>}> $actions label => intent payload
     */
    public function remember(int $chatId, array $actions): void
    {
        $item = $this->cache->getItem($this->key($chatId));
        $item->set($actions);
        $this->cache->save($item);
    }

    /**
     * Looks up the Activity+payload bound to $label for $chatId, or
     * null if this chat has no Reply keyboard currently showing that
     * label (including: no Reply keyboard sent at all, a plain-text
     * Reply button with no intent attached, or a stale label from a
     * keyboard that has since been replaced).
     *
     * @return array{a?: string, p?: array, f?: list<string>}|null
     */
    public function get(int $chatId, string $label): ?array
    {
        $item = $this->cache->getItem($this->key($chatId));
        $actions = $item->isHit() ? $item->get() : [];

        return $actions[$label] ?? null;
    }

    private function key(int $chatId): string
    {
        return self::CACHE_KEY_PREFIX . $chatId;
    }
}
