<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Board;
use App\Domain\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Board>
 */
class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    /**
     * @return Board[]
     */
    public function findByProject(Project $project): array
    {
        /** @var Board[] $results */
        $results = $this->createQueryBuilder('b')
            ->where('b.project = :project')
            ->setParameter('project', $project)
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }
}
