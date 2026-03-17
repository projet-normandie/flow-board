<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Security\CardVoter;
use App\Domain\Entity\ChecklistItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checklist-items')]
#[IsGranted('ROLE_USER')]
class ChecklistItemController extends AbstractController
{
    #[Route('/{id}/update-title', name: 'app_checklist_item_update_title', methods: ['POST'])]
    public function updateTitle(
        Request $request,
        ChecklistItem $item,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $item->getChecklist()->getCard());

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('update_item_title' . $item->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $title = trim($request->request->getString('value'));
        if ($title === '') {
            return new JsonResponse(['error' => 'Title cannot be empty'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $item->setTitle($title);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/toggle', name: 'app_checklist_item_toggle', methods: ['POST'])]
    public function toggle(
        Request $request,
        ChecklistItem $item,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(CardVoter::EDIT, $item->getChecklist()->getCard());

        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('toggle_item' . $item->getId(), $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $item->setChecked(!$item->isChecked());
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'checked' => $item->isChecked()]);
    }
}
