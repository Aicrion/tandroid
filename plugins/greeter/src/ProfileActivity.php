<?php

declare(strict_types=1);

namespace Greeter;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;

#[IntentFilter(action: 'VIEW_PROFILE')]
final class ProfileActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $userId = $this->update->userId;

        $this->setContentView(
            View::message("شناسه کاربری شما: {$userId}"),
        );

        return null;
    }
}