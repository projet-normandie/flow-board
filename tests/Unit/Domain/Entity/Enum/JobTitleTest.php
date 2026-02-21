<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Enum;

use App\Domain\Entity\Enum\JobTitle;
use PHPUnit\Framework\TestCase;

final class JobTitleTest extends TestCase
{
    public function testCases(): void
    {
        $cases = JobTitle::cases();
        self::assertCount(4, $cases);
    }

    public function testValues(): void
    {
        self::assertSame('developer', JobTitle::DEVELOPER->value);
        self::assertSame('tester', JobTitle::TESTER->value);
        self::assertSame('sys_admin', JobTitle::SYS_ADMIN->value);
        self::assertSame('product_owner', JobTitle::PRODUCT_OWNER->value);
    }

    public function testFromValue(): void
    {
        self::assertSame(JobTitle::DEVELOPER, JobTitle::from('developer'));
        self::assertSame(JobTitle::PRODUCT_OWNER, JobTitle::from('product_owner'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        JobTitle::from('invalid');
    }
}
