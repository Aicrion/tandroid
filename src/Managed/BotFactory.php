<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Managed;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps Telegram Bot API 9.6's managed-bot lifecycle
 * (getManagedBotToken / replaceManagedBotToken), letting a parent
 * bot spawn and administer child bots programmatically — the
 * PackageInstaller-like capability that lets a single Aicrion\Tandroid
 * deployment provision a dedicated bot per client/tenant instead of
 * routing everyone through one shared token.
 */
final class BotFactory
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $parentToken,
    ) {}

    public function createManagedBot(string $name, string $username): ManagedBot
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->parentToken}/createManagedBot",
            ['json' => ['name' => $name, 'username' => $username]],
        );

        $result = $response->toArray()['result'];

        return new ManagedBot($result['bot_id'], $result['username'], $result['token']);
    }

    public function rotateToken(int $botId): string
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->parentToken}/replaceManagedBotToken",
            ['json' => ['bot_id' => $botId]],
        );

        return $response->toArray()['result']['token'];
    }

    public function revoke(int $botId): void
    {
        $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->parentToken}/deleteManagedBot",
            ['json' => ['bot_id' => $botId]],
        );
    }
}