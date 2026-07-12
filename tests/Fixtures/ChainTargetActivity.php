<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Fixtures;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;

final class ChainTargetActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $this->setContentView(View::message('landed:' . $intent->getExtra('from')));

        return null;
    }
}