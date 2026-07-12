<?php

declare(strict_types=1);

/**
 * Persian translations. Values may be plain strings with :placeholder
 * tokens, or closures for full Expression support (pluralization,
 * conditional wording, computed formatting, etc.).
 */
return [
    'created' => fn (int $count) => match (true) {
        $count === 1 => 'یک سفارش با موفقیت ثبت شد ✅',
        $count > 1 => "{$count} سفارش با موفقیت ثبت شد ✅",
        default => 'هیچ سفارشی ثبت نشد',
    },

    'total_price' => fn (int $amount) => sprintf('مبلغ کل: %s تومان', number_format($amount)),

    'greeting' => 'سلام :name، خوش آمدید!',
];
