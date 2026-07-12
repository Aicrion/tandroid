<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * A single page within a WizardWidget: static text plus its own
 * grid of action buttons, independent of the Prev/Next nav row
 * which WizardWidget appends automatically.
 */
final class WizardPage
{
    /** @param list<list<Button>> $buttons */
    public function __construct(
        public readonly string $text,
        public readonly array $buttons = [],
    ) {}
}