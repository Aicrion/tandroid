<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;

/**
 * Built-in "404 Activity" — the ActivityManager's target whenever
 * IntentResolver can't match any registered #[IntentFilter] against
 * an incoming Update. The Kernel registers this Activity directly
 * (it does not belong to any plugin manifest), so it is always
 * available even on a completely empty installation.
 *
 * Host applications can override the fallback experience by binding
 * their own BotActivity to Kernel::withFallbackActivity() before
 * boot() — see docs/kernel.md.
 */
final class FallbackActivityMarker extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $this->setContentView(
            View::message('متوجه این دستور نشدم 🤔 لطفاً از دکمه‌های موجود استفاده کنید یا /start را بفرستید.'),
        );

        return null;
    }
}
