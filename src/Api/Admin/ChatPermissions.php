<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Admin;

/**
 * Immutable, fluent value object for the ChatPermissions type used
 * by restrictChatMember/setChatPermissions — replaces the raw
 * associative array Telegram expects with named, discoverable setters.
 */
final class ChatPermissions
{
    private array $flags = [];

    public static function make(): self
    {
        return new self();
    }

    public function allowSendMessages(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->flags['can_send_messages'] = $allow;

        return $clone;
    }

    public function allowSendMedia(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->flags['can_send_media_messages'] = $allow;

        return $clone;
    }

    public function allowSendPolls(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->flags['can_send_polls'] = $allow;

        return $clone;
    }

    public function allowInviteUsers(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->flags['can_invite_users'] = $allow;

        return $clone;
    }

    public function allowPinMessages(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->flags['can_pin_messages'] = $allow;

        return $clone;
    }

    public function toArray(): array
    {
        return $this->flags;
    }
}