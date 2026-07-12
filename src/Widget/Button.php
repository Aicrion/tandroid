<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * A single inline/reply button. `action` buttons encode a target
 * Activity FQCN + payload into callback_data so the IntentResolver
 * can reconstruct an explicit Intent on tap.
 */
final class Button
{
    private function __construct(
        public readonly string $label,
        public readonly ?string $callbackData = null,
        public readonly ?string $url = null,
        public readonly bool $requestContact = false,
    ) {}

    public static function action(string $label, string $to, array $payload = []): self
    {
        $data = json_encode(['a' => $to, 'p' => $payload], JSON_THROW_ON_ERROR);

        return new self($label, callbackData: $data);
    }

    public static function url(string $label, string $url): self
    {
        return new self($label, url: $url);
    }

    public static function requestContact(string $label): self
    {
        return new self($label, requestContact: true);
    }
}
