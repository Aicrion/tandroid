<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Works around Telegram's hard 64-byte limit on inline keyboard
 * callback_data. An explicit-intent button (Button::action() /
 * Button::actionReplace()) needs to carry the target Activity's
 * FQCN plus any payload/flags — that alone regularly blows past 64
 * bytes once a plugin's namespace gets more than a couple of
 * segments deep (e.g. "App\Plugins\Greeter\ProfileActivity" already
 * costs 35 of the 64 bytes on its own).
 *
 * Instead of sending that payload to Telegram directly, it is
 * stored server-side (in the framework's existing cache pool — see
 * Cache\CachePoolFactory) keyed by a short deterministic hash of its
 * own content. Only that hash — always a fixed 16 bytes, regardless
 * of how deep a plugin's namespace is — is ever sent as
 * callback_data. When the button is tapped, IntentResolver looks
 * the hash back up here to recover the original payload. Same idea
 * as Nutgram's InlineMenu callback storage, adapted to this
 * framework's cache abstraction.
 *
 * Hashing is deterministic (sha256 of the payload's canonical JSON),
 * so re-rendering the exact same button — e.g. the same "⬅ Back"
 * button shown across many messages — reuses the same cache entry
 * and the same wire-level callback_data instead of minting a new one
 * every time.
 *
 * Usable two ways:
 *   - as a normal injectable service (constructor takes the cache
 *     pool directly) — this is how IntentResolver receives it via
 *     the DI container, keeping it easy to unit test with a plain
 *     ArrayAdapter and no static state.
 *   - as a static facade, configured once in Kernel::boot() exactly
 *     like the Telegram class already is — this is what Button/
 *     Keyboard::render() use, since widget rendering happens deep
 *     inside View::render() with no DI container in scope.
 */
final class CallbackDataStore
{
    private const string CACHE_KEY_PREFIX = 'aicrion.cbdata.';

    /**
     * How long a minted token stays resolvable. Generous on purpose:
     * old messages with inline keyboards can be tapped long after
     * they were sent, and a stale token should fail gracefully (the
     * tap falls through to the implicit/fallback Activity) rather
     * than silently do the wrong thing, but it shouldn't expire
     * while the conversation is still realistically active either.
     */
    private const int TTL_SECONDS = 7 * 24 * 60 * 60;

    /** Length, in hex characters, of the minted token. 16 hex chars
     * = 64 bits of the underlying sha256 digest — negligible
     * collision risk for any realistic number of distinct buttons a
     * single bot will ever render, while leaving Telegram's 64-byte
     * callback_data budget almost entirely free for future use. */
    private const int TOKEN_LENGTH = 16;

    private static ?self $facade = null;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    /**
     * Configures the static facade used by Button/Keyboard. Called
     * once from Kernel::boot() with the same cache pool instance
     * used everywhere else in the framework.
     */
    public static function configure(CacheItemPoolInterface $cache): void
    {
        self::$facade = new self($cache);
    }

    /**
     * The globally-configured instance, or null if Kernel::boot()
     * was never called (e.g. a Button built directly in a unit test).
     */
    public static function global(): ?self
    {
        return self::$facade;
    }

    /**
     * Stores $payload and returns a short opaque token safe to use
     * directly as Telegram callback_data.
     */
    public function put(array $payload): string
    {
        $token = $this->hash($payload);
        $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . $token);

        $item->set($payload);
        $item->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);

        return $token;
    }

    /**
     * Recovers a payload previously stored by put(). Returns null if
     * the token is unknown or has expired — callers should treat
     * that as "this button is stale", not as an error.
     */
    public function get(string $token): ?array
    {
        $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . $token);

        return $item->isHit() ? $item->get() : null;
    }

    private function hash(array $payload): string
    {
        $digest = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return substr($digest, 0, self::TOKEN_LENGTH);
    }
}