<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\LoginHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/login-history', name: 'admin_user_login_history_all', methods: ['GET'])]
    public function loginHistoryAll(LoginHistoryRepository $loginHistoryRepository): Response
    {
        return $this->render('@App/admin/users/login_history_all.html.twig', [
            'loginHistories' => $loginHistoryRepository->findLatest(100),
        ]);
    }

    #[Route('/{id}/login-history', name: 'admin_user_login_history', methods: ['GET'])]
    public function loginHistory(User $user, LoginHistoryRepository $loginHistoryRepository): Response
    {
        return $this->render('@App/admin/users/login_history.html.twig', [
            'user' => $user,
            'loginHistories' => $loginHistoryRepository->findByUser($user, 50),
        ]);
    }
}
