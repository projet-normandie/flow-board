<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Board;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BoardController extends AbstractController
{
    #[Route('/board/{id}', name: 'app_board', methods: ['GET'])]
    public function index(Board $board): Response
    {
        return $this->redirectToRoute('app_project_board', [
            'id' => $board->getProject()->getId(),
            'boardId' => $board->getId(),
        ], Response::HTTP_MOVED_PERMANENTLY);
    }
}
