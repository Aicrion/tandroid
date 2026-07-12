<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Fixtures;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Intent\Intent;

/**
 * Never sets a content view itself — immediately chains to
 * ChainTargetActivity, exercising the ActivityManager's ability to
 * follow a NavigationRequest within a single incoming Update.
 */
final class ChainStartActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        return $this->startActivity(Intent::to(ChainTargetActivity::class)->putExtra('from', 'chain-start'));
    }
}
