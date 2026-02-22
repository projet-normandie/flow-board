<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Card;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/archive')]
#[IsGranted('ROLE_ADMIN')]
class ArchiveController extends AbstractController
{
    #[Route('', name: 'app_archive_index', methods: ['GET'])]
    public function index(
        CardRepository $cardRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        return $this->render('@App/archive/index.html.twig', [
            'cards' => $cardRepository->findArchivedCards($entityManager),
        ]);
    }

    #[Route('/{id}/restore', name: 'app_archive_restore', methods: ['POST'])]
    public function restore(
        Request $request,
        int $id,
        CardRepository $cardRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $filters = $entityManager->getFilters();
        $filtersEnabled = $filters->isEnabled('softdeleteable');

        if ($filtersEnabled) {
            $filters->disable('softdeleteable');
        }

        $card = $cardRepository->find($id);

        if ($card instanceof Card) {
            $token = $request->request->getString('_token');

            if ($this->isCsrfTokenValid('restore' . $card->getId(), $token)) {
                $card->setDeletedAt(null);
                $entityManager->flush();

                $this->addFlash('success', 'archive.flash.restored');
            }
        }

        if ($filtersEnabled) {
            $filters->enable('softdeleteable');
        }

        return $this->redirectToRoute('app_archive_index');
    }
}
