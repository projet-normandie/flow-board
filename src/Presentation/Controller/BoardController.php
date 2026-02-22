<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BoardController extends AbstractController
{
    #[Route('/board', name: 'app_board', methods: ['GET'])]
    public function index(
        Request $request,
        ColumnRepository $columnRepository,
        ProjectRepository $projectRepository,
    ): Response {
        $projectId = $request->query->getInt('project');
        $selectedProject = $projectId > 0 ? $projectRepository->find($projectId) : null;

        return $this->render('@App/board/index.html.twig', [
            'columns' => $columnRepository->findBy([], ['position' => 'ASC']),
            'projects' => $projectRepository->findAll(),
            'selectedProject' => $selectedProject,
        ]);
    }
}
