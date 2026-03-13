<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Checklist;
use App\Domain\Entity\ChecklistItem;
use PHPUnit\Framework\TestCase;

final class ChecklistItemTest extends TestCase
{
    private ChecklistItem $item;

    protected function setUp(): void
    {
        $this->item = new ChecklistItem();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->item->getId());
    }

    public function testTitle(): void
    {
        $this->item->setTitle('Write unit tests');
        self::assertSame('Write unit tests', $this->item->getTitle());
    }

    public function testPosition(): void
    {
        $this->item->setPosition(1000);
        self::assertSame(1000, $this->item->getPosition());
    }

    public function testCheckedIsFalseByDefault(): void
    {
        self::assertFalse($this->item->isChecked());
    }

    public function testSetChecked(): void
    {
        $this->item->setChecked(true);
        self::assertTrue($this->item->isChecked());

        $this->item->setChecked(false);
        self::assertFalse($this->item->isChecked());
    }

    public function testChecklist(): void
    {
        $checklist = new Checklist();
        $checklist->setTitle('QA');
        $checklist->setPosition(1000);

        $this->item->setChecklist($checklist);
        self::assertSame($checklist, $this->item->getChecklist());
    }

    public function testFluentSetters(): void
    {
        $checklist = new Checklist();

        $result = $this->item
            ->setTitle('Deploy to production')
            ->setPosition(2000)
            ->setChecked(true)
            ->setChecklist($checklist);

        self::assertSame($this->item, $result);
    }
}
