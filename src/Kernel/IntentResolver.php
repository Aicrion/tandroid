<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Intent\IntentFlag;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\Update\UpdateType;

/**
 * Resolves an incoming Update into either an explicit Intent
 * (when a callback_data payload references a concrete Activity, or
 * a plain text message matches a currently-visible Reply keyboard
 * action button) or an implicit one matched against every registered
 * #[IntentFilter], ordered by priority — directly analogous to
 * Android's intent resolution against the manifest's
 * <intent-filter> entries.
 */
final class IntentResolver
{
    /**
     * @param array<class-string, list<IntentFilter>> $registry Activity FQCN => filters
     */
    public function __construct(
        private readonly array $registry,
        private readonly ?CallbackDataStore $callbackDataStore = null,
        private readonly ?ReplyActionStore $replyActionStore = null,
    ) {}

    public function resolve(Update $update): Intent
    {
        if ($update->type === UpdateType::CallbackQuery && $update->callbackData !== null) {
            $decoded = $this->decodeCallbackData($update->callbackData);
            $intent = $decoded !== null ? $this->intentFromPayload($decoded) : null;

            if ($intent !== null) {
                return $intent;
            }
        }

        if ($update->type === UpdateType::Message && $update->text !== null) {
            $decoded = $this->replyActionStore?->get($update->chatId, $update->text);
            $intent = $decoded !== null ? $this->intentFromPayload($decoded) : null;

            if ($intent !== null) {
                return $intent;
            }
        }

        return $this->resolveImplicit($update);
    }

    /**
     * callback_data for an explicit-intent button arrives in one of
     * two shapes:
     *   - the normal case: a short opaque token minted by
     *     CallbackDataStore::put() (see Button::resolveCallbackData()),
     *     which keeps every button safely under Telegram's 64-byte
     *     callback_data limit regardless of how long the target
     *     Activity's FQCN is.
     *   - a raw JSON-encoded payload — kept working for backward
     *     compatibility with callback_data built by hand, by an
     *     older framework version, or directly in tests.
     *
     * @return array{a?: string, p?: array, f?: list<string>}|null
     */
    private function decodeCallbackData(string $callbackData): ?array
    {
        $decoded = json_decode($callbackData, associative: true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return $this->callbackDataStore?->get($callbackData);
    }

    /**
     * Shared by both the callback_query path and the Reply-keyboard
     * path (see ReplyActionStore): both ultimately resolve the same
     * {"a": ..., "p": ..., "f": ...} shape into an explicit Intent.
     *
     * @param array{a?: string, p?: array, f?: list<string>} $decoded
     */
    private function intentFromPayload(array $decoded): ?Intent
    {
        if (!isset($decoded['a'])) {
            return null;
        }

        $intent = Intent::to($decoded['a']);

        foreach ($decoded['p'] ?? [] as $key => $value) {
            $intent = $intent->putExtra($key, $value);
        }

        foreach ($decoded['f'] ?? [] as $flagName) {
            foreach (IntentFlag::cases() as $flag) {
                if ($flag->name === $flagName) {
                    $intent = $intent->withFlag($flag);
                    break;
                }
            }
        }

        return $intent;
    }

    private function resolveImplicit(Update $update): Intent
    {
        $candidates = [];

        foreach ($this->registry as $activityClass => $filters) {
            foreach ($filters as $filter) {
                if ($this->matches($filter, $update)) {
                    $candidates[] = [$activityClass, $filter->priority];
                }
            }
        }

        usort($candidates, static fn ($a, $b) => $b[1] <=> $a[1]);

        $target = $candidates[0][0] ?? FallbackActivityMarker::class;

        return Intent::to($target)->putExtra('raw_text', $update->text);
    }

    private function matches(IntentFilter $filter, Update $update): bool
    {
        if ($filter->pattern !== null && $update->text !== null) {
            return (bool) preg_match($filter->pattern, $update->text);
        }

        return $filter->action === 'MAIN' && $update->type === UpdateType::Message;
    }
}
