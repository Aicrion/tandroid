<?php

declare(strict_types=1);

namespace Greeter\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Greeter\Entity\Subscriber;

/**
 * Thin repository over Doctrine's EntityManager. Plugins are free to
 * use raw Doctrine repositories, custom ones like this, or DBAL
 * directly — the framework only requires that entities be declared
 * in the plugin's manifest.php so the Kernel wires the correct
 * metadata/connection at boot time.
 */
final class SubscriberRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findByTelegramId(int $telegramUserId): ?Subscriber
    {
        return $this->entityManager->getRepository(Subscriber::class)
            ->findOneBy(['telegramUserId' => $telegramUserId]);
    }

    public function save(Subscriber $subscriber): void
    {
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();
    }
}
