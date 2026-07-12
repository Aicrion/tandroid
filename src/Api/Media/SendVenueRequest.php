<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SendVenueRequest
{
    private int|string|null $chatId = null;

    private ?float $latitude = null;

    private ?float $longitude = null;

    private ?string $title = null;

    private ?string $address = null;

    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function to(int|string $chatId): self
    {
        $clone = clone $this;
        $clone->chatId = $chatId;

        return $clone;
    }

    public function at(float $latitude, float $longitude): self
    {
        $clone = clone $this;
        $clone->latitude = $latitude;
        $clone->longitude = $longitude;

        return $clone;
    }

    public function venue(string $title, string $address): self
    {
        $clone = clone $this;
        $clone->title = $title;
        $clone->address = $address;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendVenue",
            ['json' => [
                'chat_id' => $this->chatId,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'title' => $this->title,
                'address' => $this->address,
            ]],
        );

        return $response->toArray();
    }
}