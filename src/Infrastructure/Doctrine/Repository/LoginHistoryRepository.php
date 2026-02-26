<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\LoginHistory;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginHistory>
 */
class LoginHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginHistory::class);
    }

    /**
     * @return list<LoginHistory>
     */
    public function findByUser(User $user, int $limit = 10): array
    {
        /** @var list<LoginHistory> $results */
        $results = $this->createQueryBuilder('lh')
            ->andWhere('lh.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lh.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * @return list<LoginHistory>
     */
    public function findLatest(int $limit = 50): array
    {
        /** @var list<LoginHistory> $results */
        $results = $this->createQueryBuilder('lh')
            ->orderBy('lh.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $results;
    }
}
