<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Enum;

use App\Domain\Entity\Enum\CardPriority;
use PHPUnit\Framework\TestCase;

final class CardPriorityTest extends TestCase
{
    public function testCases(): void
    {
        $cases = CardPriority::cases();
        self::assertCount(4, $cases);
    }

    public function testValues(): void
    {
        self::assertSame('low', CardPriority::LOW->value);
        self::assertSame('medium', CardPriority::MEDIUM->value);
        self::assertSame('high', CardPriority::HIGH->value);
        self::assertSame('critical', CardPriority::CRITICAL->value);
    }

    public function testFromValue(): void
    {
        self::assertSame(CardPriority::LOW, CardPriority::from('low'));
        self::assertSame(CardPriority::CRITICAL, CardPriority::from('critical'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        CardPriority::from('invalid');
    }
}
