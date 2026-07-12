<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Remote;

/**
 * Describes a message directed at another bot rather than a human
 * user, leveraging Telegram Bot API 10.0's bot-to-bot messaging.
 * Conceptually the cross-process equivalent of Intent — instead of
 * navigating to a local Activity, it addresses a remote bot by
 * username and carries an arbitrary structured payload the receiving
 * bot's own IntentResolver can interpret.
 */
final class RemoteIntent
{
    /** @var array<string, mixed> */
    private array $payload = [];

    private function __construct(
        public readonly string $targetBotUsername,
        public readonly string $action,
    ) {}

    public static function to(string $targetBotUsername, string $action): self
    {
        return new self(ltrim($targetBotUsername, '@'), $action);
    }

    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->payload[$key] = $value;

        return $clone;
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'payload' => $this->payload,
        ];
    }
}
