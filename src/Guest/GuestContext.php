<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Guest;

/**
 * Represents the restricted execution context created when a user
 * invokes the bot via @mention inside a foreign chat without adding
 * it (Bot API 10.0 Guest Mode). Attached to the Update so Activities
 * know they only see the single triggering message — not the full
 * chat history or membership — and must scope any side effects
 * (e.g. database writes) accordingly.
 */
final class GuestContext
{
    public function __construct(
        public readonly int $hostChatId,
        public readonly int $mentioningUserId,
        public readonly bool $isGuestInvocation = true,
        public readonly ?string $guestQueryId = null,
    ) {}

    /**
     * Activities should call this before touching anything that
     * assumes full chat membership (join lists, admin checks, etc.).
     */
    public function assertFullAccess(): void
    {
        if ($this->isGuestInvocation) {
            throw new \RuntimeException('This action requires the bot to be added to the chat; guest mentions have restricted access.');
        }
    }

    /**
     * Guest-mode responses must be sent through answerGuestQuery
     * rather than a normal sendMessage, since the bot has no
     * standing membership in the host chat to post into directly.
     */
    public function answerQueryId(): ?string
    {
        return $this->guestQueryId;
    }
}
