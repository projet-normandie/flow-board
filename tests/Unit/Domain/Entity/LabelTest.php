<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Label;
use PHPUnit\Framework\TestCase;

final class LabelTest extends TestCase
{
    private Label $label;

    protected function setUp(): void
    {
        $this->label = new Label();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->label->getId());
    }

    public function testName(): void
    {
        $this->label->setName('Bug');
        self::assertSame('Bug', $this->label->getName());
    }

    public function testColor(): void
    {
        $this->label->setColor('#ef4444');
        self::assertSame('#ef4444', $this->label->getColor());
    }

    public function testCardsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->label->getCards());
    }

    public function testFluentSetters(): void
    {
        $result = $this->label
            ->setName('Feature')
            ->setColor('#3b82f6');

        self::assertSame($this->label, $result);
    }
}
