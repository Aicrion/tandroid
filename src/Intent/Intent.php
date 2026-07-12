<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Intent;

/**
 * A message describing an operation to be performed, or a navigation
 * target, exactly like android.content.Intent. Can be explicit
 * (targets a concrete Activity FQCN) or implicit (resolved by action).
 */
final class Intent
{
    /** @var array<string, mixed> */
    private array $extras = [];

    /** @var list<IntentFlag> */
    private array $flags = [];

    private function __construct(
        public readonly ?string $activityClass = null,
        public readonly ?string $action = null,
        public readonly ?string $category = null,
    ) {}

    public static function to(string $activityClass): self
    {
        return new self(activityClass: $activityClass);
    }

    public static function action(string $action, ?string $category = null): self
    {
        return new self(action: $action, category: $category);
    }

    public function putExtra(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->extras[$key] = $value;

        return $clone;
    }

    public function withFlag(IntentFlag $flag): self
    {
        $clone = clone $this;
        $clone->flags[] = $flag;

        return $clone;
    }

    public function getExtra(string $key, mixed $default = null): mixed
    {
        return $this->extras[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /** @return list<IntentFlag> */
    public function getFlags(): array
    {
        return $this->flags;
    }

    public function hasFlag(IntentFlag $flag): bool
    {
        return in_array($flag, $this->flags, strict: true);
    }

    public function isExplicit(): bool
    {
        return $this->activityClass !== null;
    }
}