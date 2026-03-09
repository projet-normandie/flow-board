<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Enum;

use App\Domain\Entity\Enum\AuthType;
use PHPUnit\Framework\TestCase;

final class AuthTypeTest extends TestCase
{
    public function testCases(): void
    {
        self::assertCount(2, AuthType::cases());
    }

    public function testValues(): void
    {
        self::assertSame('form_login', AuthType::FORM_LOGIN->value);
        self::assertSame('remember_me', AuthType::REMEMBER_ME->value);
    }

    public function testFromValue(): void
    {
        self::assertSame(AuthType::FORM_LOGIN, AuthType::from('form_login'));
        self::assertSame(AuthType::REMEMBER_ME, AuthType::from('remember_me'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        AuthType::from('invalid');
    }
}
