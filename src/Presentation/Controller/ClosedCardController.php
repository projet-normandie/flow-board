<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Infrastructure\Doctrine\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/closed-cards')]
#[IsGranted('ROLE_USER')]
class ClosedCardController extends AbstractController
{
    #[Route('', name: 'app_closed_card_index', methods: ['GET'])]
    public function index(CardRepository $cardRepository): Response
    {
        return $this->render('@App/closed_card/index.html.twig', [
            'cards' => $cardRepository->findClosedCards(),
        ]);
    }
}
