<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(UserRepository::class);
        assert($repository instanceof UserRepository);
        $this->repository = $repository;
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testUpgradePasswordUpdatesUserPassword(): void
    {
        $user = (new User())
            ->setEmail('upgrade@test.com')
            ->setPassword('old_hash')
            ->setFullName('Test User');

        $this->em->persist($user);
        $this->em->flush();

        $this->repository->upgradePassword($user, 'new_hash');

        self::assertSame('new_hash', $user->getPassword());
    }

    public function testUpgradePasswordPersistsToDatabase(): void
    {
        $user = (new User())
            ->setEmail('persist@test.com')
            ->setPassword('old_hash')
            ->setFullName('Test User');

        $this->em->persist($user);
        $this->em->flush();
        $userId = $user->getId();

        $this->repository->upgradePassword($user, 'persisted_hash');

        $this->em->clear();
        $refreshed = $this->repository->find($userId);

        self::assertNotNull($refreshed);
        self::assertSame('persisted_hash', $refreshed->getPassword());
    }
}
