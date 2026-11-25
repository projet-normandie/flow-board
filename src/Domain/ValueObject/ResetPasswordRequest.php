<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Entity\User;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

/**
 * Wraps the reset password request fields stored directly on {@see User}.
 *
 * There is no dedicated table: a user has at most one pending reset
 * request, which is overwritten on each new request, so no history is kept.
 *
 * @implements ResetPasswordRequestInterface<User>
 */
final readonly class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    public function __construct(
        private User $user,
        private string $selector,
        private string $hashedToken,
        private \DateTimeImmutable $requestedAt,
        private \DateTimeImmutable $expiresAt,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->getTimestamp() <= (new \DateTimeImmutable())->getTimestamp();
    }
}
