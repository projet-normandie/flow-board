<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Column;
use PHPUnit\Framework\TestCase;

final class ColumnTest extends TestCase
{
    private Column $column;

    protected function setUp(): void
    {
        $this->column = new Column();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->column->getId());
    }

    public function testName(): void
    {
        $this->column->setName('To Do');
        self::assertSame('To Do', $this->column->getName());
    }

    public function testPosition(): void
    {
        $this->column->setPosition(1000);
        self::assertSame(1000, $this->column->getPosition());
    }

    public function testCardsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->column->getCards());
    }

    public function testFluentSetters(): void
    {
        $result = $this->column
            ->setName('Done')
            ->setPosition(5000);

        self::assertSame($this->column, $result);
    }
}
