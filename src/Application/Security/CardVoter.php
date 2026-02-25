<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Domain\Entity\Card;
use App\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Card>
 */
class CardVoter extends Voter
{
    public const string VIEW = 'CARD_VIEW';
    public const string EDIT = 'CARD_EDIT';
    public const string DELETE = 'CARD_DELETE';

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
        return $token->getUser() instanceof User;
    }
}
