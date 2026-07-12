<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SendPollRequest
{
    private int|string|null $chatId = null;

    private ?string $question = null;

    private array $options = [];

    private bool $isAnonymous = true;

    private bool $allowsMultipleAnswers = false;

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

    public function question(string $question): self
    {
        $clone = clone $this;
        $clone->question = $question;

        return $clone;
    }

    public function options(string ...$options): self
    {
        $clone = clone $this;
        $clone->options = array_values($options);

        return $clone;
    }

    public function allowMultiple(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->allowsMultipleAnswers = $allow;

        return $clone;
    }

    public function anonymous(bool $anonymous = true): self
    {
        $clone = clone $this;
        $clone->isAnonymous = $anonymous;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendPoll",
            ['json' => [
                'chat_id' => $this->chatId,
                'question' => $this->question,
                'options' => $this->options,
                'is_anonymous' => $this->isAnonymous,
                'allows_multiple_answers' => $this->allowsMultipleAnswers,
            ]],
        );

        return $response->toArray();
    }
}
