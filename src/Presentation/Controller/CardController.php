<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\CardVoter;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Label;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Presentation\Twig\GravatarExtension;
use App\Application\Service\ActivityService;
use App\Presentation\Form\CardFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cards')]
#[IsGranted('ROLE_USER')]
class CardController extends AbstractController
{
    #[Route('/new', name: 'app_card_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CardRepository $cardRepository,
    ): Response {
        $card = new Card();
        $form = $this->createForm(CardFormType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $card->setAuthor($user);

            /** @var Column $column */
            $column = $card->getColumn();
            $card->setPosition($cardRepository->getNextPositionInColumn($column));

            $entityManager->persist($card);
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.created');

            return $this->redirectToRoute('app_project_board', [
                'id' => $column->getBoard()->getProject()->getId(),
                'boardId' => $column->getBoard()->getId(),
            ]);
        }

        return $this->render('@App/cards/new.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/column/{id}/new', name: 'app_column_card_new', methods: ['GET', 'POST'])]
    public function newInColumn(
        Column $column,
        Request $request,
        EntityManagerInterface $entityManager,
        CardRepository $cardRepository,
    ): Response {
        $card = new Card();
        $card->setColumn($column);
        $form = $this->createForm(CardFormType::class, $card, ['quick_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $card->setAuthor($user);
            $card->setPosition($cardRepository->getNextPositionInColumn($column));

            $entityManager->persist($card);
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.created');

            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('app_project_board', [
                    'id' => $column->getBoard()->getProject()->getId(),
                    'boardId' => $column->getBoard()->getId(),
                ]),
            ]);
        }

        return $this->render('@App/cards/_new_modal.html.twig', [
            'column' => $column,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'app_card_show', methods: ['GET'])]
    public function show(
        Card $card,
        ActivityService $activityService,
        LabelRepository $labelRepository,
        UserRepository $userRepository,
    ): Response {
        return $this->render('@App/cards/_show_modal.html.twig', [
            'card' => $card,
            'timeline' => $activityService->getCardTimeline($card),
            'allLabels' => $labelRepository->findAll(),
            'allUsers' => $userRepository->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_card_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): Response {
        $token = $request->request->getString('_token');
        /** @var Column $column */
        $column = $card->getColumn();
        $board = $column->getBoard();
        $projectId = $board->getProject()->getId();
        $boardId = $board->getId();

        if ($this->isCsrfTokenValid('delete' . $card->getId(), $token)) {
            $entityManager->remove($card);
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.deleted');
        }

        $redirectUrl = $this->generateUrl('app_project_board', ['id' => $projectId, 'boardId' => $boardId]);

        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return new JsonResponse(['success' => true, 'redirect' => $redirectUrl]);
        }

        return $this->redirect($redirectUrl);
    }

    #[Route('/{id}/update-title', name: 'app_card_update_title', methods: ['POST'])]
    public function updateTitle(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_title' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $title = trim($request->request->getString('value'));
        if ($title === '') {
            return new JsonResponse(['error' => 'Title cannot be empty'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $card->setTitle($title);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/update-assignees', name: 'app_card_update_assignees', methods: ['POST'])]
    public function updateAssignees(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        GravatarExtension $gravatar,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_assignees' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        /** @var list<string> $userIds */
        $userIds = $request->request->all('userIds');

        foreach ($card->getAssignees()->toArray() as $assignee) {
            $card->removeAssignee($assignee);
        }

        foreach ($userIds as $userId) {
            $user = $userRepository->find((int) $userId);
            if ($user !== null) {
                $card->addAssignee($user);
            }
        }

        $entityManager->flush();

        $usersData = array_values(array_map(
            static fn (User $u): array => [
                'id' => $u->getId(),
                'fullName' => $u->getFullName(),
                'gravatarUrl' => $gravatar->gravatar($u->getEmail()),
            ],
            $card->getAssignees()->toArray()
        ));

        return new JsonResponse(['success' => true, 'assignees' => $usersData]);
    }

    #[Route('/{id}/update-labels', name: 'app_card_update_labels', methods: ['POST'])]
    public function updateLabels(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
        LabelRepository $labelRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_labels' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        /** @var list<string> $labelIds */
        $labelIds = $request->request->all('labelIds');

        foreach ($card->getLabels()->toArray() as $label) {
            $card->removeLabel($label);
        }

        foreach ($labelIds as $labelId) {
            $label = $labelRepository->find((int) $labelId);
            if ($label !== null) {
                $card->addLabel($label);
            }
        }

        $entityManager->flush();

        $labelsData = array_values(array_map(
            static fn (Label $l): array => ['id' => $l->getId(), 'name' => $l->getName(), 'color' => $l->getColor()],
            $card->getLabels()->toArray()
        ));

        return new JsonResponse(['success' => true, 'labels' => $labelsData]);
    }

    #[Route('/{id}/update-due-date', name: 'app_card_update_due_date', methods: ['POST'])]
    public function updateDueDate(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_due_date' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $dateStr = trim($request->request->getString('dueDate'));

        if ($dateStr === '') {
            $card->setDueDate(null);
        } else {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
            if ($date === false) {
                return new JsonResponse(['error' => 'Invalid date format'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $card->setDueDate($date);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/update-reported-by', name: 'app_card_update_reported_by', methods: ['POST'])]
    public function updateReportedBy(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_reported_by' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $value = trim($request->request->getString('value'));
        $card->setReportedBy($value ?: null);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/update-priority', name: 'app_card_update_priority', methods: ['POST'])]
    public function updatePriority(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_priority' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $priorityValue = $request->request->getString('priority');
        $priority = $priorityValue !== '' ? CardPriority::tryFrom($priorityValue) : null;

        if ($priorityValue !== '' && $priority === null) {
            return new JsonResponse(['error' => 'Invalid priority value'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $card->setPriority($priority);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/update-description', name: 'app_card_update_description', methods: ['POST'])]
    public function updateDescription(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_description' . $card->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $description = $request->request->getString('description');
        $card->setDescription($description ?: null);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/move', name: 'app_card_move', methods: ['POST'])]
    public function move(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
        ColumnRepository $columnRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        /** @var array{columnId?: int, position?: int} $data */
        $data = json_decode($request->getContent(), true);

        $columnId = $data['columnId'] ?? null;
        $position = $data['position'] ?? null;

        if ($columnId === null || $position === null) {
            return new JsonResponse(['error' => 'Missing columnId or position'], Response::HTTP_BAD_REQUEST);
        }

        $column = $columnRepository->find($columnId);

        if (!$column instanceof Column) {
            return new JsonResponse(['error' => 'Column not found'], Response::HTTP_NOT_FOUND);
        }

        $card->setColumn($column);
        $card->setPosition($position);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
