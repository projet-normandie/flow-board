<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Enum\AuthType;
use App\Domain\Entity\LoginHistory;
use App\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class LoginHistoryTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('user@test.com')->setFullName('Test User');
    }

    public function testConstructorSetsAllFields(): void
    {
        $history = new LoginHistory($this->user, '127.0.0.1', 'Mozilla/5.0', AuthType::FORM_LOGIN);

        self::assertSame($this->user, $history->getUser());
        self::assertSame('127.0.0.1', $history->getIpAddress());
        self::assertSame('Mozilla/5.0', $history->getUserAgent());
        self::assertSame(AuthType::FORM_LOGIN, $history->getAuthType());
    }

    public function testIdIsNullByDefault(): void
    {
        $history = new LoginHistory($this->user, '127.0.0.1', 'Mozilla/5.0', AuthType::FORM_LOGIN);
        self::assertNull($history->getId());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $history = new LoginHistory($this->user, '127.0.0.1', 'Mozilla/5.0', AuthType::REMEMBER_ME);
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $history->getCreatedAt());
        self::assertLessThanOrEqual($after, $history->getCreatedAt());
    }

    public function testRememberMeAuthType(): void
    {
        $history = new LoginHistory($this->user, '192.168.1.1', 'Chrome/120', AuthType::REMEMBER_ME);
        self::assertSame(AuthType::REMEMBER_ME, $history->getAuthType());
    }
}
