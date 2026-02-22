<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Domain\Entity\Card;
use App\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @extends Voter<string, Card>
 */
class CardVoter extends Voter
{
    public const string VIEW = 'CARD_VIEW';
    public const string EDIT = 'CARD_EDIT';
    public const string DELETE = 'CARD_DELETE';

    public function __construct(
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Card;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());

        if (in_array('ROLE_ADMIN', $reachableRoles, true)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT => $this->isAssignedOrAuthor($subject, $user),
            self::DELETE => false,
            default => false,
        };
    }

    private function isAssignedOrAuthor(Card $card, User $user): bool
    {
        if ($card->getAuthor() === $user) {
            return true;
        }

        return $card->getAssignees()->contains($user);
    }
}
