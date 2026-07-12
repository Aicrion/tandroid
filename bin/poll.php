<?php

declare(strict_types=1);

/**
 * Long-running polling daemon entry point, for environments where a
 * persistent process is available (VPS, Docker, systemd service).
 * Not required on shared hosting — use public/webhook.php instead.
 */

require __DIR__ . '/../vendor/autoload.php';

use Aicrion\Tandroid\Kernel\Kernel;
use Aicrion\Tandroid\Update\PollingUpdateSource;

$kernel = Kernel::fromConfigFile(__DIR__ . '/../config/aicrion.yaml')->boot();
$source = new PollingUpdateSource($kernel->httpClient(), $kernel->config()->botToken);

echo "Aicrion Tandroid — polling started. Press Ctrl+C to stop.\n";

while (true) {
    foreach ($source->pull() as $update) {
        try {
            $kernel->handle($update);
        } catch (\Throwable $exception) {
            fwrite(STDERR, sprintf("[aicrion] update #%d failed: %s\n", $update->updateId, $exception->getMessage()));
        }
    }

    usleep(200_000);
}