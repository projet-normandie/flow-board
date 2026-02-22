<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Entity\Label;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Presentation\Form\LabelFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/labels')]
#[IsGranted('ROLE_ADMIN')]
class LabelController extends AbstractController
{
    #[Route('', name: 'admin_label_index', methods: ['GET'])]
    public function index(LabelRepository $labelRepository): Response
    {
        return $this->render('@App/admin/labels/index.html.twig', [
            'labels' => $labelRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_label_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $label = new Label();
        $form = $this->createForm(LabelFormType::class, $label);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($label);
            $entityManager->flush();

            $this->addFlash('success', 'Label created successfully.');

            return $this->redirectToRoute('admin_label_index');
        }

        return $this->render('@App/admin/labels/new.html.twig', [
            'label' => $label,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_label_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Label $label, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LabelFormType::class, $label);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Label updated successfully.');

            return $this->redirectToRoute('admin_label_index');
        }

        return $this->render('@App/admin/labels/edit.html.twig', [
            'label' => $label,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_label_delete', methods: ['POST'])]
    public function delete(Request $request, Label $label, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->getString('_token');

        if ($this->isCsrfTokenValid('delete' . $label->getId(), $token)) {
            $entityManager->remove($label);
            $entityManager->flush();

            $this->addFlash('success', 'Label deleted successfully.');
        }

        return $this->redirectToRoute('admin_label_index');
    }
}
