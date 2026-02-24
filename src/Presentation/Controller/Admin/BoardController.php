<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Entity\Board;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Presentation\Form\BoardFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boards')]
#[IsGranted('ROLE_ADMIN')]
class BoardController extends AbstractController
{
    #[Route('', name: 'admin_board_index', methods: ['GET'])]
    public function index(BoardRepository $boardRepository): Response
    {
        return $this->render('@App/admin/boards/index.html.twig', [
            'boards' => $boardRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_board_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $board = new Board();
        $form = $this->createForm(BoardFormType::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($board);
            $entityManager->flush();

            $this->addFlash('success', 'board.flash.created');

            return $this->redirectToRoute('admin_board_index');
        }

        return $this->render('@App/admin/boards/new.html.twig', [
            'board' => $board,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_board_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Board $board, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BoardFormType::class, $board);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'board.flash.updated');

            return $this->redirectToRoute('admin_board_index');
        }

        return $this->render('@App/admin/boards/edit.html.twig', [
            'board' => $board,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_board_delete', methods: ['POST'])]
    public function delete(Request $request, Board $board, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->getString('_token');

        if ($this->isCsrfTokenValid('delete' . $board->getId(), $token)) {
            $entityManager->remove($board);
            $entityManager->flush();

            $this->addFlash('success', 'board.flash.deleted');
        }

        return $this->redirectToRoute('admin_board_index');
    }
}
