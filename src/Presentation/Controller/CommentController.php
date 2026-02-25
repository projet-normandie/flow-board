<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Card;
use App\Domain\Entity\Comment;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comments')]
#[IsGranted('ROLE_USER')]
class CommentController extends AbstractController
{
    #[Route('/{id}/create', name: 'app_comment_create', methods: ['POST'])]
    public function create(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
    ): Response {
        $content = trim($request->request->getString('content'));

        if ($content === '') {
            return $this->renderShowModal($card, $commentRepository);
        }

        /** @var User $user */
        $user = $this->getUser();

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setAuthor($user);
        $comment->setCard($card);

        $entityManager->persist($comment);
        $entityManager->flush();

        return $this->renderShowModal($card, $commentRepository);
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Comment $comment,
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
    ): Response {
        $card = $comment->getCard();
        $token = $request->request->getString('_token');

        if ($this->isCsrfTokenValid('delete_comment' . $comment->getId(), $token)) {
            /** @var User $user */
            $user = $this->getUser();

            if ($comment->getAuthor() === $user || $this->isGranted('ROLE_ADMIN')) {
                $entityManager->remove($comment);
                $entityManager->flush();
            }
        }

        return $this->renderShowModal($card, $commentRepository);
    }

    private function renderShowModal(Card $card, CommentRepository $commentRepository): Response
    {
        return $this->render('@App/cards/_show_modal.html.twig', [
            'card' => $card,
            'comments' => $commentRepository->findByCard($card),
        ]);
    }
}
