<?php

declare(strict_types=1);

namespace App\Presentation\EventSubscriber;

use App\Domain\Entity\Enum\AuthType;
use App\Domain\Entity\LoginHistory;
use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginHistorySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $ipAddress = $request->getClientIp() ?? '0.0.0.0';
        $userAgent = $request->headers->get('User-Agent', '');

        $authType = $event->getAuthenticator() instanceof RememberMeAuthenticator
            ? AuthType::REMEMBER_ME
            : AuthType::FORM_LOGIN;

        $loginHistory = new LoginHistory($user, $ipAddress, $userAgent, $authType);

        $this->entityManager->persist($loginHistory);
        $this->entityManager->flush();
    }
}
