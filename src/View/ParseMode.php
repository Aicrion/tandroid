<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View;

enum ParseMode: string
{
    case MarkdownV2 = 'MarkdownV2';
    case Html = 'HTML';
    case Plain = '';
}
