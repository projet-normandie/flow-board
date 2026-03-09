<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProjectRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProjectRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(ProjectRepository::class);
        assert($repository instanceof ProjectRepository);
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
        self::assertInstanceOf(ProjectRepository::class, $this->repository);
    }

    public function testFindAllReturnsProjects(): void
    {
        $project = (new Project())->setName('Test Project')->setColor('#3b82f6');
        $this->em->persist($project);
        $this->em->flush();

        $results = $this->repository->findAll();

        self::assertNotEmpty($results);
        self::assertContains($project, $results);
    }
}
