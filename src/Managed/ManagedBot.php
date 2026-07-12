<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Managed;

/**
 * Value object describing a child bot spawned through BotFactory.
 * Each ManagedBot can boot its own Kernel instance with its own
 * token/config, effectively letting one Aicrion\Tandroid codebase
 * run many independent bot "processes" — analogous to how a single
 * Android device can run multiple app instances under different
 * user profiles.
 */
final class ManagedBot
{
    public function __construct(
        public readonly int $botId,
        public readonly string $username,
        public readonly string $token,
    ) {}
}