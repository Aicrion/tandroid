<?php

declare(strict_types=1);

use Aicrion\Tandroid\Package\Manifest;
use Aicrion\Tandroid\Package\Permission;
use Greeter\Entity\Subscriber;
use Greeter\ProfileActivity;
use Greeter\RelayActivity;
use Greeter\StartActivity;
use Greeter\WelcomeReceiver;

return Manifest::define(
    package: 'greeter',
    version: '1.0.0',
    activities: [StartActivity::class, ProfileActivity::class, RelayActivity::class],
    permissions: [Permission::SendMessage, Permission::BroadcastEvents],
    entities: [Subscriber::class],
    receivers: [WelcomeReceiver::class],
);
