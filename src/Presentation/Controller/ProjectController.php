<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\CardVoter;
use App\Domain\Entity\Card;
use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        UrlGeneratorInterface $urlGenerator,
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

        $filterMine = $request->query->getBoolean('mine');
        /** @var User|null $user */
        $user = $this->getUser();
        $assigneeId = $filterMine ? $user?->getId() : null;

        $cards = $cardRepository->findByBoard($board, $priority, $labelIds, $assigneeId);

        $cardsByColumn = [];
        foreach ($cards as $card) {
            /** @var \App\Domain\Entity\Column $column */
            $column = $card->getColumn();
            $cardsByColumn[(int) $column->getId()][] = $card;
        }

        $boards = $boardRepository->findByProject($project);

        $boardJson = $this->buildBoardJson($board->getColumns()->toArray(), $cardsByColumn, $urlGenerator);

        return $this->render('@App/project/board.html.twig', [
            'project' => $project,
            'board' => $board,
            'boards' => $boards,
            'selectedPriority' => $priority,
            'priorities' => CardPriority::cases(),
            'labels' => $labelRepository->findAll(),
            'selectedLabels' => $labelIds,
            'filterMine' => $filterMine,
            'boardJson' => $boardJson,
        ]);
    }

    /**
     * @param \App\Domain\Entity\Column[] $columns
     * @param array<int, Card[]> $cardsByColumn
     */
    private function buildBoardJson(array $columns, array $cardsByColumn, UrlGeneratorInterface $urlGenerator): string
    {
        $now = new \DateTimeImmutable();

        $columnsData = array_map(function (\App\Domain\Entity\Column $column) use ($cardsByColumn, $urlGenerator, $now): array {
            $cards = $cardsByColumn[$column->getId() ?? 0] ?? [];

            $cardsData = array_map(function (Card $card) use ($urlGenerator, $now): array {
                $dueDate = $card->getDueDate();
                $dueDateDiffDays = null;
                $dueDateFormatted = null;

                if ($dueDate !== null) {
                    $dueDateFormatted = $dueDate->format('d/m/Y');
                    $diff = $now->diff($dueDate);
                    $dueDateDiffDays = (int) ($diff->invert ? -$diff->days : $diff->days);
                }

                $assignees = array_map(static function (User $assignee): array {
                    $hash = md5(strtolower(trim($assignee->getEmail())));

                    return [
                        'id' => $assignee->getId(),
                        'fullName' => $assignee->getFullName(),
                        'gravatar' => "https://www.gravatar.com/avatar/{$hash}?d=mp&s=26",
                    ];
                }, $card->getAssignees()->toArray());

                $labels = array_map(static fn (\App\Domain\Entity\Label $label): array => [
                    'id' => $label->getId(),
                    'name' => $label->getName(),
                    'color' => $label->getColor(),
                ], $card->getLabels()->toArray());

                return [
                    'id' => $card->getId(),
                    'title' => $card->getTitle(),
                    'priority' => $card->getPriority()?->value,
                    'dueDate' => $dueDateFormatted,
                    'dueDateDiffDays' => $dueDateDiffDays,
                    'labels' => $labels,
                    'assignees' => $assignees,
                    'canEdit' => $this->isGranted(CardVoter::EDIT, $card),
                    'showUrl' => $urlGenerator->generate('app_card_show', ['id' => $card->getId()]),
                    'commentsCount' => $card->getComments()->count(),
                    'moveUrl' => $urlGenerator->generate('app_card_move', ['id' => $card->getId()]),
                ];
            }, $cards);

            return [
                'id' => $column->getId(),
                'name' => $column->getName(),
                'position' => $column->getPosition(),
                'moveUrl' => $urlGenerator->generate('app_column_move', ['id' => $column->getId()]),
                'newCardUrl' => $urlGenerator->generate('app_column_card_new', ['id' => $column->getId()]),
                'cards' => $cardsData,
            ];
        }, $columns);

        return (string) json_encode([
            'columns' => $columnsData,
            'canAddCard' => $this->isGranted('ROLE_USER'),
        ]);
    }
}
