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

    public function testCommentsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->user->getComments());
    }

    public function testLoginHistoriesCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->user->getLoginHistories());
    }

    public function testToString(): void
    {
        $this->user->setFullName('John Doe');
        self::assertSame('John Doe', (string) $this->user);
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
            ->setEnabled(false);

        self::assertSame($this->user, $result);
    }
}
