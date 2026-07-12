<?php

declare(strict_types=1);

return [
    'created' => fn (int $count) => match (true) {
        $count === 1 => 'One order was placed successfully ✅',
        $count > 1 => "{$count} orders were placed successfully ✅",
        default => 'No order was placed',
    },

    'total_price' => fn (int $amount) => sprintf('Total: $%s', number_format($amount)),

    'greeting' => 'Hello :name, welcome!',
];