<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\CardVoter;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
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
            $card->setPosition($cardRepository->getNextPositionInColumn($card->getColumn()));

            $entityManager->persist($card);
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.created');

            return $this->redirectToRoute('app_board');
        }

        return $this->render('@App/cards/new.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'app_card_show', methods: ['GET'])]
    public function show(Card $card): Response
    {
        return $this->render('@App/cards/_show_modal.html.twig', [
            'card' => $card,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $form = $this->createForm(CardFormType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.updated');

            return $this->redirectToRoute('app_board');
        }

        return $this->render('@App/cards/edit.html.twig', [
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

        if ($this->isCsrfTokenValid('delete' . $card->getId(), $token)) {
            $entityManager->remove($card);
            $entityManager->flush();

            $this->addFlash('success', 'card.flash.deleted');
        }

        return $this->redirectToRoute('app_board');
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
