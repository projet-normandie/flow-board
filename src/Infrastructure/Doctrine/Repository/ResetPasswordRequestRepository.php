<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\ResetPasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * Stores the pending reset password request directly on the {@see User} entity
 * instead of in a dedicated table: no history is kept, a new request simply
 * overwrites the previous one.
 *
 * @implements ResetPasswordRequestRepositoryInterface<User>
 */
class ResetPasswordRequestRepository implements ResetPasswordRequestRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function createResetPasswordRequest(
        object $user,
        \DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken,
    ): ResetPasswordRequestInterface {
        return new ResetPasswordRequest(
            $user,
            $selector,
            $hashedToken,
            new \DateTimeImmutable(),
            \DateTimeImmutable::createFromInterface($expiresAt),
        );
    }

    public function getUserIdentifier(object $user): string
    {
        return (string) $user->getId();
    }

    public function persistResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        assert($resetPasswordRequest instanceof ResetPasswordRequest);

        $resetPasswordRequest->getUser()->setResetPasswordRequest(
            $resetPasswordRequest->getSelector(),
            $resetPasswordRequest->getHashedToken(),
            $resetPasswordRequest->getRequestedAt(),
            $resetPasswordRequest->getExpiresAt(),
        );

        $this->entityManager->flush();
    }

    public function findResetPasswordRequest(string $selector): ?ResetPasswordRequestInterface
    {
        $user = $this->userRepository->findOneBy(['resetPasswordSelector' => $selector]);

        if (!$user instanceof User) {
            return null;
        }

        $hashedToken = $user->getResetPasswordHashedToken();
        $requestedAt = $user->getResetPasswordRequestedAt();
        $expiresAt = $user->getResetPasswordExpiresAt();

        if (null === $hashedToken || null === $requestedAt || null === $expiresAt) {
            return null;
        }

        return new ResetPasswordRequest($user, $selector, $hashedToken, $requestedAt, $expiresAt);
    }

    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        $requestedAt = $user->getResetPasswordRequestedAt();
        $expiresAt = $user->getResetPasswordExpiresAt();

        if (null === $requestedAt || null === $expiresAt || $expiresAt->getTimestamp() <= time()) {
            return null;
        }

        return $requestedAt;
    }

    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        assert($resetPasswordRequest instanceof ResetPasswordRequest);

        $resetPasswordRequest->getUser()->setResetPasswordRequest(null, null, null, null);
        $this->entityManager->flush();
    }

    public function removeExpiredResetPasswordRequests(): int
    {
        $affected = $this->entityManager->createQuery(
            'UPDATE App\Domain\Entity\User u
             SET u.resetPasswordSelector = null, u.resetPasswordHashedToken = null,
                 u.resetPasswordRequestedAt = null, u.resetPasswordExpiresAt = null
             WHERE u.resetPasswordExpiresAt <= :time'
        )->setParameter('time', new \DateTimeImmutable('-1 week'))->execute();

        assert(is_int($affected));

        return $affected;
    }
}
