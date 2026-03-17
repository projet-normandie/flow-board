<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\CardVoter;
use App\Application\Service\ActivityService;
use App\Domain\Entity\Card;
use App\Domain\Entity\Checklist;
use App\Domain\Entity\ChecklistItem;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChecklistController extends AbstractController
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly LabelRepository $labelRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/cards/{id}/checklists', name: 'app_card_checklist_create', methods: ['POST'])]
    public function create(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('create_checklist' . $card->getId(), $token)) {
            return $this->renderShowModal($card);
        }

        $title = trim($request->request->getString('title'));
        if ($title !== '') {
            $checklist = new Checklist();
            $checklist->setTitle($title);
            $checklist->setPosition(($card->getChecklists()->count() + 1) * 1000);
            $card->addChecklist($checklist);
            $entityManager->persist($checklist);
            $entityManager->flush();
        }

        return $this->renderShowModal($card);
    }

    #[Route('/checklists/{id}/items', name: 'app_checklist_item_create', methods: ['POST'])]
    public function createItem(
        Request $request,
        Checklist $checklist,
        EntityManagerInterface $entityManager,
    ): Response {
        $card = $checklist->getCard();
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $card);

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('create_item' . $checklist->getId(), $token)) {
            return $this->renderShowModal($card);
        }

        $title = trim($request->request->getString('title'));
        if ($title !== '') {
            $item = new ChecklistItem();
            $item->setTitle($title);
            $item->setPosition(($checklist->getItems()->count() + 1) * 1000);
            $checklist->addItem($item);
            $entityManager->persist($item);
            $entityManager->flush();
        }

        return $this->renderShowModal($card);
    }

    #[Route('/checklists/{id}/update-title', name: 'app_checklist_update_title', methods: ['POST'])]
    public function updateTitle(
        Request $request,
        Checklist $checklist,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $checklist->getCard());

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_checklist_title' . $checklist->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $title = trim($request->request->getString('value'));
        if ($title === '') {
            return new JsonResponse(['error' => 'Title cannot be empty'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $checklist->setTitle($title);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    private function renderShowModal(Card $card): Response
    {
        return $this->render('@App/cards/_show_modal.html.twig', [
            'card' => $card,
            'timeline' => $this->activityService->getCardTimeline($card),
            'allLabels' => $this->labelRepository->findAll(),
            'allUsers' => $this->userRepository->findAll(),
        ]);
    }
}
