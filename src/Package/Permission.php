<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Package;

enum Permission: string
{
    case WriteDatabase = 'write_database';
    case SendMessage = 'send_message';
    case ManageUsers = 'manage_users';
    case AccessNetwork = 'access_network';
    case BroadcastEvents = 'broadcast_events';
}