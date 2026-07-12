<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Stickers;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps sticker-set management methods (createNewStickerSet,
 * addStickerToSet, sendSticker) so plugins can ship or generate
 * branded sticker packs as part of an onboarding or gamification flow.
 */
final class StickerSetManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function createSet(int $userId, string $name, string $title, array $stickers): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/createNewStickerSet",
            ['json' => [
                'user_id' => $userId,
                'name' => $name,
                'title' => $title,
                'stickers' => $stickers,
            ]],
        );

        return $response->toArray();
    }

    public function addSticker(int $userId, string $name, array $sticker): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/addStickerToSet",
            ['json' => ['user_id' => $userId, 'name' => $name, 'sticker' => $sticker]],
        );

        return $response->toArray();
    }

    public function send(int|string $chatId, string $stickerFileIdOrUrl): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendSticker",
            ['json' => ['chat_id' => $chatId, 'sticker' => $stickerFileIdOrUrl]],
        );

        return $response->toArray();
    }
}
