<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Update;

/**
 * Abstraction over how updates enter the system. PollingUpdateSource
 * long-polls getUpdates(); WebhookUpdateSource parses the current
 * HTTP request body. The Kernel is agnostic to which one is active.
 */
interface UpdateSourceInterface
{
    /**
     * @return iterable<Update>
     */
    public function pull(): iterable;
}
