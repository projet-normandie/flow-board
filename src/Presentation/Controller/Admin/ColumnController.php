<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Entity\Column;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Presentation\Form\ColumnFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/columns')]
#[IsGranted('ROLE_ADMIN')]
class ColumnController extends AbstractController
{
    #[Route('', name: 'admin_column_index', methods: ['GET'])]
    public function index(ColumnRepository $columnRepository): Response
    {
        return $this->render('@App/admin/columns/index.html.twig', [
            'columns' => $columnRepository->findBy([], ['position' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_column_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $column = new Column();
        $form = $this->createForm(ColumnFormType::class, $column);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($column);
            $entityManager->flush();

            $this->addFlash('success', 'Column created successfully.');

            return $this->redirectToRoute('admin_column_index');
        }

        return $this->render('@App/admin/columns/new.html.twig', [
            'column' => $column,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_column_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Column $column, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ColumnFormType::class, $column);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Column updated successfully.');

            return $this->redirectToRoute('admin_column_index');
        }

        return $this->render('@App/admin/columns/edit.html.twig', [
            'column' => $column,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_column_delete', methods: ['POST'])]
    public function delete(Request $request, Column $column, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->getString('_token');

        if ($this->isCsrfTokenValid('delete' . $column->getId(), $token)) {
            $entityManager->remove($column);
            $entityManager->flush();

            $this->addFlash('success', 'Column deleted successfully.');
        }

        return $this->redirectToRoute('admin_column_index');
    }
}
