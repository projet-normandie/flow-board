<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\CardVoter;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\CommentRepository;
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
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
    public function newInColumn(
        Column $column,
        Request $request,
        EntityManagerInterface $entityManager,
        CardRepository $cardRepository,
    ): Response {
        $card = new Card();
        $form = $this->createForm(CardFormType::class, $card, ['quick_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $card->setColumn($column);
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
    public function show(Card $card, CommentRepository $commentRepository): Response
    {
        return $this->render('@App/cards/_show_modal.html.twig', [
            'card' => $card,
            'comments' => $commentRepository->findByCard($card),
        ]);
    }

    #[Route('/{id}/edit-modal', name: 'app_card_edit_modal', methods: ['GET', 'POST'])]
    public function editModal(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $form = $this->createForm(CardFormType::class, $card, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.updated');

            /** @var Column $column */
            $column = $card->getColumn();

            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('app_project_board', [
                    'id' => $column->getBoard()->getProject()->getId(),
                    'boardId' => $column->getBoard()->getId(),
                ]),
            ]);
        }

        return $this->render('@App/cards/_edit_modal.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_card_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
