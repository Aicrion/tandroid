<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

/**
 * Contract for a single structured content block within a Bot API
 * 10.1 "Rich Message" (table, list, quote, code, map, slideshow...).
 * A View can attach many RichBlocks; RichMessage flattens them into
 * the `content` array the API expects, replacing plain
 * text+parse_mode for bots that need real structured layout instead
 * of Markdown approximations.
 */
interface RichBlockInterface
{
    public function render(): array;
}