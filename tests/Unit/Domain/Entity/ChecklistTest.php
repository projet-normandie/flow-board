<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Card;
use App\Domain\Entity\Checklist;
use App\Domain\Entity\ChecklistItem;
use PHPUnit\Framework\TestCase;

final class ChecklistTest extends TestCase
{
    private Checklist $checklist;

    protected function setUp(): void
    {
        $this->checklist = new Checklist();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->checklist->getId());
    }

    public function testTitle(): void
    {
        $this->checklist->setTitle('Definition of Done');
        self::assertSame('Definition of Done', $this->checklist->getTitle());
    }

    public function testPosition(): void
    {
        $this->checklist->setPosition(1000);
        self::assertSame(1000, $this->checklist->getPosition());
    }

    public function testCard(): void
    {
        $card = new Card();
        $this->checklist->setCard($card);
        self::assertSame($card, $this->checklist->getCard());
    }

    public function testItemsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->checklist->getItems());
    }

    public function testAddItem(): void
    {
        $item = new ChecklistItem();
        $item->setTitle('Write unit tests');
        $item->setPosition(1000);

        $this->checklist->addItem($item);

        self::assertCount(1, $this->checklist->getItems());
        self::assertTrue($this->checklist->getItems()->contains($item));
        self::assertSame($this->checklist, $item->getChecklist());
    }

    public function testAddItemIsIdempotent(): void
    {
        $item = new ChecklistItem();
        $item->setTitle('Write unit tests');
        $item->setPosition(1000);

        $this->checklist->addItem($item);
        $this->checklist->addItem($item);

        self::assertCount(1, $this->checklist->getItems());
    }

    public function testRemoveItem(): void
    {
        $item = new ChecklistItem();
        $item->setTitle('Write unit tests');
        $item->setPosition(1000);

        $this->checklist->addItem($item);
        $this->checklist->removeItem($item);

        self::assertCount(0, $this->checklist->getItems());
    }

    public function testGetTotalCount(): void
    {
        self::assertSame(0, $this->checklist->getTotalCount());

        $this->checklist->addItem($this->makeItem('A', false));
        $this->checklist->addItem($this->makeItem('B', true));
        $this->checklist->addItem($this->makeItem('C', false));

        self::assertSame(3, $this->checklist->getTotalCount());
    }

    public function testGetCheckedCount(): void
    {
        self::assertSame(0, $this->checklist->getCheckedCount());

        $this->checklist->addItem($this->makeItem('A', false));
        $this->checklist->addItem($this->makeItem('B', true));
        $this->checklist->addItem($this->makeItem('C', true));

        self::assertSame(2, $this->checklist->getCheckedCount());
    }

    public function testGetCheckedCountWhenAllUnchecked(): void
    {
        $this->checklist->addItem($this->makeItem('A', false));
        $this->checklist->addItem($this->makeItem('B', false));

        self::assertSame(0, $this->checklist->getCheckedCount());
    }

    public function testFluentSetters(): void
    {
        $card = new Card();

        $result = $this->checklist
            ->setTitle('QA')
            ->setPosition(2000)
            ->setCard($card);

        self::assertSame($this->checklist, $result);
    }

    private function makeItem(string $title, bool $checked): ChecklistItem
    {
        $item = new ChecklistItem();
        $item->setTitle($title);
        $item->setPosition(1000);
        $item->setChecked($checked);

        return $item;
    }
}
