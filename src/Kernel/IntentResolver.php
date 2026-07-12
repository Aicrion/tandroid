<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\Update\UpdateType;

/**
 * Resolves an incoming Update into either an explicit Intent
 * (when a callback_data payload references a concrete Activity) or
 * an implicit one matched against every registered #[IntentFilter],
 * ordered by priority — directly analogous to Android's intent
 * resolution against the manifest's <intent-filter> entries.
 */
final class IntentResolver
{
    /**
     * @param array<class-string, list<IntentFilter>> $registry Activity FQCN => filters
     */
    public function __construct(
        private readonly array $registry,
    ) {}

    public function resolve(Update $update): Intent
    {
        if ($update->type === UpdateType::CallbackQuery && $update->callbackData !== null) {
            $decoded = json_decode($update->callbackData, associative: true);

            if (isset($decoded['a'])) {
                $intent = Intent::to($decoded['a']);

                foreach ($decoded['p'] ?? [] as $key => $value) {
                    $intent = $intent->putExtra($key, $value);
                }

                return $intent;
            }
        }

        return $this->resolveImplicit($update);
    }

    private function resolveImplicit(Update $update): Intent
    {
        $candidates = [];

        foreach ($this->registry as $activityClass => $filters) {
            foreach ($filters as $filter) {
                if ($this->matches($filter, $update)) {
                    $candidates[] = [$activityClass, $filter->priority];
                }
            }
        }

        usort($candidates, static fn ($a, $b) => $b[1] <=> $a[1]);

        $target = $candidates[0][0] ?? FallbackActivityMarker::class;

        return Intent::to($target)->putExtra('raw_text', $update->text);
    }

    private function matches(IntentFilter $filter, Update $update): bool
    {
        if ($filter->pattern !== null && $update->text !== null) {
            return (bool) preg_match($filter->pattern, $update->text);
        }

        return $filter->action === 'MAIN' && $update->type === UpdateType::Message;
    }
}
