<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Board;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Enum\CardPriority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function getNextPositionInColumn(Column $column): int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->where('c.column = :column')
            ->setParameter('column', $column)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $result) + 1000;
    }

    /**
     * @return Card[]
     */
    public function findByBoard(Board $board, ?CardPriority $priority = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.column', 'col')
            ->where('col.board = :board')
            ->setParameter('board', $board)
            ->leftJoin('c.labels', 'l')->addSelect('l')
            ->leftJoin('c.assignees', 'u')->addSelect('u')
            ->orderBy('col.position', 'ASC')
            ->addOrderBy('c.position', 'ASC');

        if ($priority !== null) {
            $qb->andWhere('c.priority = :priority')
                ->setParameter('priority', $priority);
        }

        /** @var Card[] $results */
        $results = $qb->getQuery()->getResult();

        return $results;
    }

    /**
     * @return Card[]
     */
    public function findArchivedCards(EntityManagerInterface $entityManager): array
    {
        $filters = $entityManager->getFilters();
        $filtersEnabled = $filters->isEnabled('softdeleteable');

        if ($filtersEnabled) {
            $filters->disable('softdeleteable');
        }

        /** @var Card[] $cards */
        $cards = $this->createQueryBuilder('c')
            ->where('c.deletedAt IS NOT NULL')
            ->orderBy('c.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();

        if ($filtersEnabled) {
            $filters->enable('softdeleteable');
        }

        return $cards;
    }
}
