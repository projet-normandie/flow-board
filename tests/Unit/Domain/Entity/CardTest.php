<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Label;
use App\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class CardTest extends TestCase
{
    private Card $card;

    protected function setUp(): void
    {
        $this->card = new Card();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->card->getId());
    }

    public function testTitle(): void
    {
        $this->card->setTitle('My Card');
        self::assertSame('My Card', $this->card->getTitle());
    }

    public function testDescription(): void
    {
        self::assertNull($this->card->getDescription());

        $this->card->setDescription('A description');
        self::assertSame('A description', $this->card->getDescription());

        $this->card->setDescription(null);
        self::assertNull($this->card->getDescription());
    }

    public function testPosition(): void
    {
        $this->card->setPosition(1000);
        self::assertSame(1000, $this->card->getPosition());
    }

    public function testDueDate(): void
    {
        self::assertNull($this->card->getDueDate());

        $date = new \DateTimeImmutable('2026-03-01');
        $this->card->setDueDate($date);
        self::assertSame($date, $this->card->getDueDate());

        $this->card->setDueDate(null);
        self::assertNull($this->card->getDueDate());
    }

    public function testPriority(): void
    {
        self::assertNull($this->card->getPriority());

        $this->card->setPriority(CardPriority::HIGH);
        self::assertSame(CardPriority::HIGH, $this->card->getPriority());

        $this->card->setPriority(null);
        self::assertNull($this->card->getPriority());
    }

    public function testAuthor(): void
    {
        $user = new User();
        $user->setEmail('author@test.com');
        $this->card->setAuthor($user);
        self::assertSame($user, $this->card->getAuthor());
    }

    public function testColumn(): void
    {
        $column = new Column();
        $column->setName('To Do');
        $this->card->setColumn($column);
        self::assertSame($column, $this->card->getColumn());
    }

    public function testLabelsCollection(): void
    {
        self::assertCount(0, $this->card->getLabels());

        $label = new Label();
        $label->setName('Bug');

        $this->card->addLabel($label);
        self::assertCount(1, $this->card->getLabels());
        self::assertTrue($this->card->getLabels()->contains($label));
    }

    public function testAddLabelIsIdempotent(): void
    {
        $label = new Label();
        $label->setName('Bug');

        $this->card->addLabel($label);
        $this->card->addLabel($label);
        self::assertCount(1, $this->card->getLabels());
    }

    public function testRemoveLabel(): void
    {
        $label = new Label();
        $label->setName('Bug');

        $this->card->addLabel($label);
        $this->card->removeLabel($label);
        self::assertCount(0, $this->card->getLabels());
    }

    public function testAssigneesCollection(): void
    {
        self::assertCount(0, $this->card->getAssignees());

        $user = new User();
        $user->setEmail('alice@test.com');

        $this->card->addAssignee($user);
        self::assertCount(1, $this->card->getAssignees());
        self::assertTrue($this->card->getAssignees()->contains($user));
    }

    public function testAddAssigneeIsIdempotent(): void
    {
        $user = new User();
        $user->setEmail('alice@test.com');

        $this->card->addAssignee($user);
        $this->card->addAssignee($user);
        self::assertCount(1, $this->card->getAssignees());
    }

    public function testRemoveAssignee(): void
    {
        $user = new User();
        $user->setEmail('alice@test.com');

        $this->card->addAssignee($user);
        $this->card->removeAssignee($user);
        self::assertCount(0, $this->card->getAssignees());
    }

    public function testDeletedAt(): void
    {
        self::assertNull($this->card->getDeletedAt());

        $date = new \DateTimeImmutable();
        $this->card->setDeletedAt($date);
        self::assertSame($date, $this->card->getDeletedAt());

        $this->card->setDeletedAt(null);
        self::assertNull($this->card->getDeletedAt());
    }

    public function testFluentSetters(): void
    {
        $author = new User();
        $author->setEmail('author@test.com');
        $column = new Column();
        $column->setName('Backlog');

        $result = $this->card
            ->setTitle('Card')
            ->setDescription('Desc')
            ->setPosition(1000)
            ->setDueDate(new \DateTimeImmutable())
            ->setPriority(CardPriority::LOW)
            ->setAuthor($author)
            ->setColumn($column)
            ->setDeletedAt(null);

        self::assertSame($this->card, $result);
    }
}
