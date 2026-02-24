<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('@App/home/index.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }
}
