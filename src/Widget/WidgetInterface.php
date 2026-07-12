<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Base contract for every composable UI element (keyboards, forms,
 * checkboxes, lists...). Analogous to android.view.View — anything
 * that can turn itself into a fragment of the final message payload.
 */
interface WidgetInterface
{
    /**
     * @return array<string, mixed>
     */
    public function render(): array;
}
