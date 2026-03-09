<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Board;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Label;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CardRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CardRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = static::getContainer()->get(CardRepository::class);
        assert($repository instanceof CardRepository);
        $this->repository = $repository;
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testGetNextPositionInColumnWithNoCards(): void
    {
        $column = $this->createColumn();

        $position = $this->repository->getNextPositionInColumn($column);

        self::assertSame(1000, $position);
    }

    public function testGetNextPositionInColumnWithExistingCards(): void
    {
        [$column, $author] = $this->createColumnAndAuthor();

        $card = (new Card())->setTitle('C1')->setPosition(2000)->setAuthor($author)->setColumn($column);
        $this->em->persist($card);
        $this->em->flush();

        $position = $this->repository->getNextPositionInColumn($column);

        self::assertSame(3000, $position);
    }

    public function testFindByBoardReturnsAllCardsWhenNoFilters(): void
    {
        [$board, $column, $author] = $this->createBoardColumnAndAuthor();

        $card1 = (new Card())->setTitle('Card 1')->setPosition(1000)->setAuthor($author)->setColumn($column);
        $card2 = (new Card())->setTitle('Card 2')->setPosition(2000)->setAuthor($author)->setColumn($column);

        $this->em->persist($card1);
        $this->em->persist($card2);
        $this->em->flush();

        $results = $this->repository->findByBoard($board);

        self::assertCount(2, $results);
    }

    public function testFindByBoardFiltersByPriority(): void
    {
        [$board, $column, $author] = $this->createBoardColumnAndAuthor();

        $high = (new Card())->setTitle('High')->setPosition(1000)->setAuthor($author)->setColumn($column)->setPriority(CardPriority::HIGH);
        $low = (new Card())->setTitle('Low')->setPosition(2000)->setAuthor($author)->setColumn($column)->setPriority(CardPriority::LOW);

        $this->em->persist($high);
        $this->em->persist($low);
        $this->em->flush();

        $results = $this->repository->findByBoard($board, CardPriority::HIGH);

        self::assertCount(1, $results);
        self::assertSame('High', $results[0]->getTitle());
    }

    public function testFindByBoardFiltersByLabel(): void
    {
        [$board, $column, $author] = $this->createBoardColumnAndAuthor();

        $label = (new Label())->setName('Bug')->setColor('#ef4444');
        $this->em->persist($label);

        $cardWithLabel = (new Card())->setTitle('Labeled')->setPosition(1000)->setAuthor($author)->setColumn($column);
        $cardWithLabel->addLabel($label);
        $cardWithoutLabel = (new Card())->setTitle('Plain')->setPosition(2000)->setAuthor($author)->setColumn($column);

        $this->em->persist($cardWithLabel);
        $this->em->persist($cardWithoutLabel);
        $this->em->flush();

        $results = $this->repository->findByBoard($board, null, [(int) $label->getId()]);

        self::assertCount(1, $results);
        self::assertSame('Labeled', $results[0]->getTitle());
    }

    public function testFindByBoardFiltersByAssignee(): void
    {
        [$board, $column, $author] = $this->createBoardColumnAndAuthor();

        $assignee = (new User())->setEmail('assignee@test.com')->setPassword('pass')->setFullName('Assignee');
        $this->em->persist($assignee);

        $assigned = (new Card())->setTitle('Assigned')->setPosition(1000)->setAuthor($author)->setColumn($column);
        $assigned->addAssignee($assignee);
        $unassigned = (new Card())->setTitle('Unassigned')->setPosition(2000)->setAuthor($author)->setColumn($column);

        $this->em->persist($assigned);
        $this->em->persist($unassigned);
        $this->em->flush();

        $results = $this->repository->findByBoard($board, null, [], $assignee->getId());

        self::assertCount(1, $results);
        self::assertSame('Assigned', $results[0]->getTitle());
    }

    public function testFindArchivedCardsWithFilterEnabled(): void
    {
        [$column, $author] = $this->createColumnAndAuthor();

        $filters = $this->em->getFilters();

        // Create the soft-deleted card with filter disabled so it can be persisted
        $filters->disable('softdeleteable') ;

        $archivedCard = (new Card())->setTitle('ArchivedEnabled')->setPosition(1000)->setAuthor($author)->setColumn($column);
        $archivedCard->setDeletedAt(new \DateTimeImmutable());
        $activeCard = (new Card())->setTitle('ActiveEnabled')->setPosition(2000)->setAuthor($author)->setColumn($column);

        $this->em->persist($archivedCard);
        $this->em->persist($activeCard);
        $this->em->flush();

        // Enable the filter so findArchivedCards exercises the disable/enable branches
        $filters->enable('softdeleteable');

        $results = $this->repository->findArchivedCards($this->em);

        $titles = array_map(fn (Card $c) => $c->getTitle(), $results);
        self::assertContains('ArchivedEnabled', $titles);
        self::assertNotContains('ActiveEnabled', $titles);
        // Filter must be re-enabled after the call
        self::assertTrue($filters->isEnabled('softdeleteable'));
    }

    public function testFindArchivedCardsWhenFilterAlreadyDisabled(): void
    {
        [$column, $author] = $this->createColumnAndAuthor();

        $filters = $this->em->getFilters();

        if ($filters->isEnabled('softdeleteable')) {
            $filters->disable('softdeleteable');
        }

        $archivedCard = (new Card())->setTitle('Archived2')->setPosition(1000)->setAuthor($author)->setColumn($column);
        $archivedCard->setDeletedAt(new \DateTimeImmutable());
        $this->em->persist($archivedCard);
        $this->em->flush();

        $results = $this->repository->findArchivedCards($this->em);

        $titles = array_map(fn (Card $c) => $c->getTitle(), $results);
        self::assertContains('Archived2', $titles);
        self::assertFalse($filters->isEnabled('softdeleteable'));
    }

    private function createColumn(): Column
    {
        [$column] = $this->createColumnAndAuthor();

        return $column;
    }

    /**
     * @return array{Column, User}
     */
    private function createColumnAndAuthor(): array
    {
        [$board, $column, $author] = $this->createBoardColumnAndAuthor();

        return [$column, $author];
    }

    /**
     * @return array{Board, Column, User}
     */
    private function createBoardColumnAndAuthor(): array
    {
        $project = (new Project())->setName('P')->setColor('#ffffff');
        $board = (new Board())->setName('B')->setProject($project);
        $column = (new Column())->setName('Todo')->setPosition(1000)->setBoard($board);
        $author = (new User())->setEmail(uniqid('u') . '@test.com')->setPassword('pass')->setFullName('Author');

        $this->em->persist($project);
        $this->em->persist($board);
        $this->em->persist($column);
        $this->em->persist($author);
        $this->em->flush();

        return [$board, $column, $author];
    }
}
