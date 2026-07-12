<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Info;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps getMe, getFile, and getUserProfilePhotos — the bot's own
 * identity plus generic file/profile-photo resolution used across
 * many Activities (e.g. resolving a photo file_id to a downloadable URL).
 */
final class BotInfo
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function me(): array
    {
        return $this->call('getMe', []);
    }

    public function file(string $fileId): array
    {
        return $this->call('getFile', ['file_id' => $fileId]);
    }

    public function userProfilePhotos(int $userId, int $limit = 10): array
    {
        return $this->call('getUserProfilePhotos', ['user_id' => $userId, 'limit' => $limit]);
    }

    public function fileUrl(string $filePath): string
    {
        return "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
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
