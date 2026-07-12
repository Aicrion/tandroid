<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Menu;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps setMyCommands / deleteMyCommands / setChatMenuButton — the
 * native "/" command menu and menu-button UI Telegram shows next to
 * the message box. PackageManager can aggregate every plugin's
 * declared commands (see Manifest) and push them here once at boot.
 */
final class CommandMenu
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    /**
     * @param array<string, string> $commands command => description
     */
    public function set(array $commands, ?string $languageCode = null): array
    {
        $list = array_map(
            static fn (string $command, string $description) => ['command' => $command, 'description' => $description],
            array_keys($commands),
            array_values($commands),
        );

        return $this->call('setMyCommands', array_filter(['commands' => $list, 'language_code' => $languageCode]));
    }

    public function clear(?string $languageCode = null): array
    {
        return $this->call('deleteMyCommands', array_filter(['language_code' => $languageCode]));
    }

    public function setMenuButton(int|string $chatId, array $menuButton): array
    {
        return $this->call('setChatMenuButton', ['chat_id' => $chatId, 'menu_button' => $menuButton]);
    }

    private function call(string $method, array $params): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => $params],
        );

        return $response->toArray();
    }
}
