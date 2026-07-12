<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SendContactRequest
{
    private int|string|null $chatId = null;

    private ?string $phoneNumber = null;

    private ?string $firstName = null;

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

    public function contact(string $phoneNumber, string $firstName): self
    {
        $clone = clone $this;
        $clone->phoneNumber = $phoneNumber;
        $clone->firstName = $firstName;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendContact",
            ['json' => [
                'chat_id' => $this->chatId,
                'phone_number' => $this->phoneNumber,
                'first_name' => $this->firstName,
            ]],
        );

        return $response->toArray();
    }
}
