<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('/project/{id}', name: 'app_project', methods: ['GET'])]
    public function index(Project $project, BoardRepository $boardRepository): Response
    {
        $boards = $boardRepository->findByProject($project);

        if ($boards === []) {
            return $this->render('@App/project/no_boards.html.twig', [
                'project' => $project,
            ]);
        }

        return $this->redirectToRoute('app_project_board', [
            'id' => $project->getId(),
            'boardId' => $boards[0]->getId(),
        ]);
    }

    #[Route('/project/{id}/board/{boardId}', name: 'app_project_board', methods: ['GET'])]
    public function board(
        Project $project,
        int $boardId,
        BoardRepository $boardRepository,
        CardRepository $cardRepository,
    ): Response {
        $board = $boardRepository->find($boardId);

        if ($board === null || $board->getProject()->getId() !== $project->getId()) {
            throw new NotFoundHttpException('Board not found in this project.');
        }

        $cards = $cardRepository->findByBoard($board);

        $cardsByColumn = [];
        foreach ($cards as $card) {
            $cardsByColumn[$card->getColumn()->getId()][] = $card;
        }

        $boards = $boardRepository->findByProject($project);

        return $this->render('@App/project/board.html.twig', [
            'project' => $project,
            'board' => $board,
            'boards' => $boards,
            'columns' => $board->getColumns(),
            'cardsByColumn' => $cardsByColumn,
        ]);
    }
}
