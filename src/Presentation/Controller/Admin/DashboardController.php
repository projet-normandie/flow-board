<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        ProjectRepository $projectRepository,
        BoardRepository $boardRepository,
        ColumnRepository $columnRepository,
        LabelRepository $labelRepository,
        CardRepository $cardRepository,
        UserRepository $userRepository,
    ): Response {
        return $this->render('@App/admin/dashboard.html.twig', [
            'projectCount' => $projectRepository->count(),
            'boardCount' => $boardRepository->count(),
            'columnCount' => $columnRepository->count(),
            'labelCount' => $labelRepository->count(),
            'cardCount' => $cardRepository->count(),
            'userCount' => $userRepository->count(),
        ]);
    }
}
