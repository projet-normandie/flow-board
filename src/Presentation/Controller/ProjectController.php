<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        Request $request,
        Project $project,
        int $boardId,
        BoardRepository $boardRepository,
        CardRepository $cardRepository,
        LabelRepository $labelRepository,
    ): Response {
        $board = $boardRepository->find($boardId);

        if ($board === null || $board->getProject()->getId() !== $project->getId()) {
            throw new NotFoundHttpException('Board not found in this project.');
        }

        $priority = CardPriority::tryFrom((string) $request->query->get('priority', ''));

        $labelIds = array_values(array_filter(
            array_map(
                static fn (string $v): int => (int) $v,
                array_filter($request->query->all('labels'), 'is_string'),
            ),
            static fn (int $id): bool => $id > 0,
        ));

        $cards = $cardRepository->findByBoard($board, $priority, $labelIds);

        $cardsByColumn = [];
        foreach ($cards as $card) {
            /** @var \App\Domain\Entity\Column $column */
            $column = $card->getColumn();
            $cardsByColumn[$column->getId()][] = $card;
        }

        $boards = $boardRepository->findByProject($project);

        return $this->render('@App/project/board.html.twig', [
            'project' => $project,
            'board' => $board,
            'boards' => $boards,
            'columns' => $board->getColumns(),
            'cardsByColumn' => $cardsByColumn,
            'selectedPriority' => $priority,
            'priorities' => CardPriority::cases(),
            'labels' => $labelRepository->findAll(),
            'selectedLabels' => $labelIds,
        ]);
    }
}
