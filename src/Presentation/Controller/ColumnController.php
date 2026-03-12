<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Column;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/columns')]
#[IsGranted('ROLE_USER')]
class ColumnController extends AbstractController
{
    #[Route('/{id}/move', name: 'app_column_move', methods: ['POST'])]
    public function move(
        Request $request,
        Column $column,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array{position?: int} $data */
        $data = json_decode($request->getContent(), true);

        $position = $data['position'] ?? null;

        if ($position === null) {
            return new JsonResponse(['error' => 'Missing position'], Response::HTTP_BAD_REQUEST);
        }

        $column->setPosition($position);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
