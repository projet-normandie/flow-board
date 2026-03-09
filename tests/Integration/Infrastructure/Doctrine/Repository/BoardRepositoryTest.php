<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Board;
use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BoardRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BoardRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(BoardRepository::class);
        assert($repository instanceof BoardRepository);
        $this->repository = $repository;
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testFindByProjectReturnsOnlyBoardsOfProject(): void
    {
        $project1 = (new Project())->setName('Project 1')->setColor('#3b82f6');
        $project2 = (new Project())->setName('Project 2')->setColor('#ef4444');

        $board1 = (new Board())->setName('Alpha')->setProject($project1);
        $board2 = (new Board())->setName('Beta')->setProject($project1);
        $board3 = (new Board())->setName('Gamma')->setProject($project2);

        $this->em->persist($project1);
        $this->em->persist($project2);
        $this->em->persist($board1);
        $this->em->persist($board2);
        $this->em->persist($board3);
        $this->em->flush();

        $results = $this->repository->findByProject($project1);

        self::assertCount(2, $results);
        self::assertContains($board1, $results);
        self::assertContains($board2, $results);
        self::assertNotContains($board3, $results);
    }

    public function testFindByProjectReturnsOrderedByName(): void
    {
        $project = (new Project())->setName('Project')->setColor('#3b82f6');
        $boardZ = (new Board())->setName('Zzz Board')->setProject($project);
        $boardA = (new Board())->setName('Aaa Board')->setProject($project);

        $this->em->persist($project);
        $this->em->persist($boardZ);
        $this->em->persist($boardA);
        $this->em->flush();

        $results = $this->repository->findByProject($project);

        self::assertSame('Aaa Board', $results[0]->getName());
        self::assertSame('Zzz Board', $results[1]->getName());
    }

    public function testFindByProjectReturnsEmptyForProjectWithNoBoards(): void
    {
        $project = (new Project())->setName('Empty Project')->setColor('#3b82f6');
        $this->em->persist($project);
        $this->em->flush();

        $results = $this->repository->findByProject($project);

        self::assertSame([], $results);
    }
}
