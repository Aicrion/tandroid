<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Intent;

/**
 * Behavioral flags controlling how the ActivityManager pushes/pops
 * the per-user Activity back-stack. Mirrors android.content.Intent flags.
 */
enum IntentFlag
{
    case ClearBackStack;
    case SingleTop;
    case NewTask;
    case NoHistory;
}
