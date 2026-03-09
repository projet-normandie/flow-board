<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Enum\AuthType;
use App\Domain\Entity\LoginHistory;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\LoginHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LoginHistoryRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private LoginHistoryRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(LoginHistoryRepository::class);
        assert($repository instanceof LoginHistoryRepository);
        $this->repository = $repository;
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testFindByUserReturnsOnlyHistoriesForThatUser(): void
    {
        $user1 = $this->createUser('user1@test.com');
        $user2 = $this->createUser('user2@test.com');

        $h1 = new LoginHistory($user1, '127.0.0.1', 'Chrome', AuthType::FORM_LOGIN);
        $h2 = new LoginHistory($user1, '127.0.0.2', 'Firefox', AuthType::REMEMBER_ME);
        $h3 = new LoginHistory($user2, '127.0.0.3', 'Safari', AuthType::FORM_LOGIN);

        $this->em->persist($h1);
        $this->em->persist($h2);
        $this->em->persist($h3);
        $this->em->flush();

        $results = $this->repository->findByUser($user1);

        self::assertCount(2, $results);
        foreach ($results as $result) {
            self::assertSame($user1, $result->getUser());
        }
    }

    public function testFindByUserRespectsLimit(): void
    {
        $user = $this->createUser('user@test.com');

        for ($i = 0; $i < 5; $i++) {
            $this->em->persist(new LoginHistory($user, '127.0.0.1', 'Chrome', AuthType::FORM_LOGIN));
        }
        $this->em->flush();

        $results = $this->repository->findByUser($user, 3);

        self::assertCount(3, $results);
    }

    public function testFindLatestReturnsAllHistories(): void
    {
        $user1 = $this->createUser('u1@test.com');
        $user2 = $this->createUser('u2@test.com');

        $h1 = new LoginHistory($user1, '127.0.0.1', 'Chrome', AuthType::FORM_LOGIN);
        $h2 = new LoginHistory($user2, '127.0.0.2', 'Firefox', AuthType::REMEMBER_ME);

        $this->em->persist($h1);
        $this->em->persist($h2);
        $this->em->flush();

        $results = $this->repository->findLatest(100);

        self::assertGreaterThanOrEqual(2, count($results));
    }

    public function testFindLatestRespectsLimit(): void
    {
        $user = $this->createUser('u@test.com');

        for ($i = 0; $i < 5; $i++) {
            $this->em->persist(new LoginHistory($user, '127.0.0.1', 'Chrome', AuthType::FORM_LOGIN));
        }
        $this->em->flush();

        $results = $this->repository->findLatest(3);

        self::assertCount(3, $results);
    }

    private function createUser(string $email): User
    {
        $user = (new User())->setEmail($email)->setPassword('pass')->setFullName('Test');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
