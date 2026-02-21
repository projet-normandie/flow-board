<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Card;
use App\Domain\Entity\Enum\JobTitle;
use App\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->user->getId());
    }

    public function testEmail(): void
    {
        $this->user->setEmail('test@example.com');
        self::assertSame('test@example.com', $this->user->getEmail());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $this->user->setEmail('test@example.com');
        self::assertSame('test@example.com', $this->user->getUserIdentifier());
    }

    public function testPassword(): void
    {
        $this->user->setPassword('hashed_password');
        self::assertSame('hashed_password', $this->user->getPassword());
    }

    public function testRolesAlwaysContainsRoleUser(): void
    {
        self::assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testSetRoles(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testRolesAreUnique(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_USER']);
        $roles = $this->user->getRoles();
        self::assertCount(1, $roles);
    }

    public function testFullName(): void
    {
        $this->user->setFullName('John Doe');
        self::assertSame('John Doe', $this->user->getFullName());
    }

    public function testJobTitle(): void
    {
        self::assertNull($this->user->getJobTitle());

        $this->user->setJobTitle(JobTitle::DEVELOPER);
        self::assertSame(JobTitle::DEVELOPER, $this->user->getJobTitle());

        $this->user->setJobTitle(null);
        self::assertNull($this->user->getJobTitle());
    }

    public function testEnabledByDefault(): void
    {
        self::assertTrue($this->user->isEnabled());
    }

    public function testSetEnabled(): void
    {
        $this->user->setEnabled(false);
        self::assertFalse($this->user->isEnabled());
    }

    public function testAuthoredCardsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->user->getAuthoredCards());
    }

    public function testCardsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->user->getCards());
    }

    public function testInvitationTokenIsNullByDefault(): void
    {
        self::assertNull($this->user->getInvitationToken());
        self::assertNull($this->user->getInvitationTokenExpiresAt());
    }

    public function testSetInvitationToken(): void
    {
        $this->user->setInvitationToken('abc123');
        self::assertSame('abc123', $this->user->getInvitationToken());

        $this->user->setInvitationToken(null);
        self::assertNull($this->user->getInvitationToken());
    }

    public function testSetInvitationTokenExpiresAt(): void
    {
        $date = new \DateTimeImmutable('+1 day');
        $this->user->setInvitationTokenExpiresAt($date);
        self::assertSame($date, $this->user->getInvitationTokenExpiresAt());
    }

    public function testIsInvitationTokenValidWhenValid(): void
    {
        $this->user->setInvitationToken('token123');
        $this->user->setInvitationTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        self::assertTrue($this->user->isInvitationTokenValid());
    }

    public function testIsInvitationTokenInvalidWhenExpired(): void
    {
        $this->user->setInvitationToken('token123');
        $this->user->setInvitationTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        self::assertFalse($this->user->isInvitationTokenValid());
    }

    public function testIsInvitationTokenInvalidWhenNoToken(): void
    {
        $this->user->setInvitationTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        self::assertFalse($this->user->isInvitationTokenValid());
    }

    public function testIsInvitationTokenInvalidWhenNoExpiry(): void
    {
        $this->user->setInvitationToken('token123');
        self::assertFalse($this->user->isInvitationTokenValid());
    }

    public function testEraseCredentialsDoesNotThrow(): void
    {
        $this->user->eraseCredentials();
        $this->addToAssertionCount(1);
    }

    public function testFluentSetters(): void
    {
        $result = $this->user
            ->setEmail('test@example.com')
            ->setPassword('pass')
            ->setFullName('Name')
            ->setRoles(['ROLE_ADMIN'])
            ->setJobTitle(JobTitle::TESTER)
            ->setEnabled(false)
            ->setInvitationToken('token')
            ->setInvitationTokenExpiresAt(new \DateTimeImmutable('+1 day'));

        self::assertSame($this->user, $result);
    }
}
