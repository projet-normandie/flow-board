<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Card;
use App\Domain\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[]
     */
    public function findByCard(Card $card): array
    {
        /** @var Comment[] $results */
        $results = $this->createQueryBuilder('c')
            ->where('c.card = :card')
            ->setParameter('card', $card)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $results;
    }
}
