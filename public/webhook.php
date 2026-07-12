<?php

declare(strict_types=1);

/**
 * Host entry point for webhook mode. Deployed as-is on shared
 * hosting — Telegram POSTs updates directly to this file, no
 * CLI/daemon/cron required.
 */

require __DIR__ . '/../vendor/autoload.php';

use Aicrion\Tandroid\Kernel\Kernel;
use Aicrion\Tandroid\Update\WebhookUpdateSource;

$kernel = Kernel::fromConfigFile(__DIR__ . '/../config/aicrion.yaml')->boot();

$source = new WebhookUpdateSource(file_get_contents('php://input') ?: '');

foreach ($source->pull() as $update) {
    // Kernel::handle() dispatches the Update through the matching
    // Activity AND delivers the resulting View back to Telegram —
    // nothing else is required here.
    $kernel->handle($update);
}

http_response_code(200);