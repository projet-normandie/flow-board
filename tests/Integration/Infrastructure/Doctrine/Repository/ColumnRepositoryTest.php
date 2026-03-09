<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Board;
use App\Domain\Entity\Column;
use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ColumnRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ColumnRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(ColumnRepository::class);
        assert($repository instanceof ColumnRepository);
        $this->repository = $repository;
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testRepositoryIsInstantiated(): void
    {
        self::assertInstanceOf(ColumnRepository::class, $this->repository);
    }

    public function testFindAllReturnsColumns(): void
    {
        $project = (new Project())->setName('P')->setColor('#ffffff');
        $board = (new Board())->setName('B')->setProject($project);
        $column = (new Column())->setName('To Do')->setPosition(1000)->setBoard($board);

        $this->em->persist($project);
        $this->em->persist($board);
        $this->em->persist($column);
        $this->em->flush();

        $results = $this->repository->findAll();

        self::assertContains($column, $results);
    }
}
