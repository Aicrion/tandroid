<?php

declare(strict_types=1);

namespace Greeter\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Example Doctrine entity owned by the "greeter" plugin. Declared
 * in the plugin's manifest.php `entities` list so the Kernel knows
 * to include it when compiling the ORM metadata driver, and its
 * schema is created/updated automatically via the plugin's
 * migrations/ directory — no manual doctrine:schema commands.
 */
#[ORM\Entity]
#[ORM\Table(name: 'greeter_subscribers')]
final class Subscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $telegramUserId;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'fa'])]
    private string $locale = 'fa';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct(int $telegramUserId, string $locale = 'fa')
    {
        $this->telegramUserId = $telegramUserId;
        $this->locale = $locale;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramUserId(): int
    {
        return $this->telegramUserId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
