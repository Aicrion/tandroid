<?php

declare(strict_types=1);

namespace Greeter;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\Widget\Button;
use Aicrion\Tandroid\Widget\Keyboard;

#[IntentFilter(action: 'MAIN', category: 'LAUNCHER')]
final class StartActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $this->setContentView(
            View::message('خوش آمدید به Aicrion Tandroid 👋')
                ->attach(
                    Keyboard::inline()
                        ->row(Button::action('پروفایل من', to: ProfileActivity::class))
                        ->row(Button::url('مستندات', 'https://example.com/docs')),
                ),
        );

        return null;
    }
}
