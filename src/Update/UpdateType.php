<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Update;

enum UpdateType
{
    case Message;
    case CallbackQuery;
    case InlineQuery;
    case MyChatMember;
    case ChatMember;
    case PreCheckoutQuery;
    case Unknown;
}