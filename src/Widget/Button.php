<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

use Aicrion\Tandroid\Intent\IntentFlag;
use Aicrion\Tandroid\Kernel\CallbackDataStore;

/**
 * A single inline/reply button. `action` buttons encode a target
 * Activity FQCN + payload into callback_data so the IntentResolver
 * can reconstruct an explicit Intent on tap.
 *
 * The FQCN + payload + flags are kept as a plain array
 * ($intentPayload) rather than being JSON-encoded immediately,
 * because the encoding step needs to go through CallbackDataStore
 * (see resolveCallbackData()) to stay under Telegram's 64-byte
 * callback_data limit — something a single class name easily blows
 * past on its own once a plugin's namespace has more than a couple
 * of segments.
 */
final class Button
{
    private function __construct(
        public readonly string $label,
        public readonly ?array $intentPayload = null,
        public readonly ?string $callbackData = null,
        public readonly ?string $url = null,
        public readonly bool $requestContact = false,
    ) {}

    public static function action(string $label, string $to, array $payload = []): self
    {
        return self::actionWithFlags($label, $to, $payload, []);
    }

    public static function actionReplace(string $label, string $to, array $payload = []): self
    {
        return self::actionWithFlags($label, $to, $payload, [IntentFlag::ReplaceMessage]);
    }

    /** @param list<IntentFlag> $flags */
    public static function actionWithFlags(string $label, string $to, array $payload = [], array $flags = []): self
    {
        $intentPayload = [
            'a' => $to,
            'p' => $payload,
            'f' => array_map(static fn (IntentFlag $flag) => $flag->name, $flags),
        ];

        return new self($label, intentPayload: $intentPayload);
    }

    public static function url(string $label, string $url): self
    {
        return new self($label, url: $url);
    }

    public static function requestContact(string $label): self
    {
        return new self($label, requestContact: true);
    }

    /**
     * The actual value to send Telegram as this button's
     * callback_data. Explicit-intent buttons are routed through
     * CallbackDataStore so the wire value is always a short token —
     * see CallbackDataStore's docblock for why. Falls back to the
     * raw JSON payload if no store has been configured (e.g. a
     * Button built directly in a unit test without booting the
     * Kernel), so calling code still gets a usable string rather
     * than null; that fallback can still exceed 64 bytes, which is
     * fine for tests that never talk to the real Telegram API.
     */
    public function resolveCallbackData(): ?string
    {
        if ($this->intentPayload !== null) {
            $store = CallbackDataStore::global();

            return $store !== null
                ? $store->put($this->intentPayload)
                : json_encode($this->intentPayload, JSON_THROW_ON_ERROR);
        }

        return $this->callbackData;
    }
}