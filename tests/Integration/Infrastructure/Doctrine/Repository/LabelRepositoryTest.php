<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Label;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LabelRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private LabelRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(LabelRepository::class);
        assert($repository instanceof LabelRepository);
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
        self::assertInstanceOf(LabelRepository::class, $this->repository);
    }

    public function testFindAllReturnsLabels(): void
    {
        $label = (new Label())->setName('Bug')->setColor('#ef4444');
        $this->em->persist($label);
        $this->em->flush();

        $results = $this->repository->findAll();

        self::assertContains($label, $results);
    }
}
