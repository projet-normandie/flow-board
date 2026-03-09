<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Board;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Comment;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CommentRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(CommentRepository::class);
        assert($repository instanceof CommentRepository);
        $this->repository = $repository;
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testFindByCardReturnsOnlyCommentsForThatCard(): void
    {
        [$card1, $card2, $author] = $this->createCardsAndAuthor();

        $comment1 = (new Comment())->setContent('First comment')->setAuthor($author)->setCard($card1);
        $comment2 = (new Comment())->setContent('Second comment')->setAuthor($author)->setCard($card1);
        $comment3 = (new Comment())->setContent('Other card comment')->setAuthor($author)->setCard($card2);

        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->persist($comment3);
        $this->em->flush();

        $results = $this->repository->findByCard($card1);

        self::assertCount(2, $results);
        self::assertNotContains($comment3, $results);
    }

    public function testFindByCardReturnsEmptyForCardWithNoComments(): void
    {
        [$card1] = $this->createCardsAndAuthor();

        $results = $this->repository->findByCard($card1);

        self::assertSame([], $results);
    }

    /**
     * @return array{Card, Card, User}
     */
    private function createCardsAndAuthor(): array
    {
        $project = (new Project())->setName('P')->setColor('#ffffff');
        $board = (new Board())->setName('B')->setProject($project);
        $column = (new Column())->setName('Col')->setPosition(1000)->setBoard($board);

        $author = (new User())
            ->setEmail('author@test.com')
            ->setPassword('pass')
            ->setFullName('Author');

        $card1 = (new Card())->setTitle('Card 1')->setPosition(1000)->setAuthor($author)->setColumn($column);
        $card2 = (new Card())->setTitle('Card 2')->setPosition(2000)->setAuthor($author)->setColumn($column);

        $this->em->persist($project);
        $this->em->persist($board);
        $this->em->persist($column);
        $this->em->persist($author);
        $this->em->persist($card1);
        $this->em->persist($card2);
        $this->em->flush();

        return [$card1, $card2, $author];
    }
}
