<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Admin;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps createChatInviteLink / editChatInviteLink / revokeChatInviteLink,
 * letting a plugin generate per-campaign or per-user tracked invite
 * links (e.g. one link per referral source) instead of sharing a
 * single static group link.
 */
final class ChatInviteLinkManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int|string $chatId,
    ) {}

    public function create(?string $name = null, ?int $expireDate = null, ?int $memberLimit = null, bool $createsJoinRequest = false): array
    {
        return $this->call('createChatInviteLink', array_filter([
            'name' => $name,
            'expire_date' => $expireDate,
            'member_limit' => $memberLimit,
            'creates_join_request' => $createsJoinRequest ?: null,
        ]));
    }

    public function edit(string $inviteLink, ?string $name = null): array
    {
        return $this->call('editChatInviteLink', array_filter([
            'invite_link' => $inviteLink,
            'name' => $name,
        ]));
    }

    public function revoke(string $inviteLink): array
    {
        return $this->call('revokeChatInviteLink', ['invite_link' => $inviteLink]);
    }

    private function call(string $method, array $params): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => ['chat_id' => $this->chatId, ...$params]],
        );

        return $response->toArray();
    }
}
