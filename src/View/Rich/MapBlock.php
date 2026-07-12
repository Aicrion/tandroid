<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

final class MapBlock implements RichBlockInterface
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
        private readonly ?int $zoom = 15,
    ) {}

    public function render(): array
    {
        return [
            'type' => 'map',
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'zoom' => $this->zoom,
        ];
    }
}
