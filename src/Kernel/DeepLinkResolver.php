<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Intent\Intent;

/**
 * Parses the `/start <payload>` deep-link parameter (from a link like
 * t.me/YourBot?start=order_123 or a shared invite code) into a real
 * Intent targeting the right Activity — mirroring Android's
 * Navigation Component deep links / App Links resolution. Payload
 * format is "route:key1=val1,key2=val2" by convention, but plugins
 * may register their own parsers for custom schemes.
 */
final class DeepLinkResolver
{
    /** @var array<string, class-string> route => Activity FQCN */
    private array $routes = [];

    public function registerRoute(string $route, string $activityClass): void
    {
        $this->routes[$route] = $activityClass;
    }

    public function resolve(string $payload): ?Intent
    {
        if ($payload === '') {
            return null;
        }

        [$route, $paramString] = str_contains($payload, ':')
            ? explode(':', $payload, 2)
            : [$payload, ''];

        $activityClass = $this->routes[$route] ?? null;

        if ($activityClass === null) {
            return null;
        }

        $intent = Intent::to($activityClass);

        foreach (explode(',', $paramString) as $pair) {
            if ($pair === '' || !str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $intent = $intent->putExtra($key, $value);
        }

        return $intent;
    }

    public static function extractStartPayload(?string $text): ?string
    {
        if ($text === null || !str_starts_with($text, '/start')) {
            return null;
        }

        $parts = explode(' ', $text, 2);

        return $parts[1] ?? null;
    }
}