<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Service\ActivityService;
use App\Domain\Entity\Card;
use App\Domain\Entity\Comment;
use App\Domain\Entity\User;
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
    public function __construct(
        private readonly ActivityService $activityService,
    ) {
    }

    #[Route('/{id}/create', name: 'app_comment_create', methods: ['POST'])]
    public function create(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
    ): Response {
        $content = trim($request->request->getString('content'));

        if ($content === '') {
            return $this->renderShowModal($card);
        }

        /** @var User $user */
        $user = $this->getUser();

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setAuthor($user);
        $comment->setCard($card);

        $entityManager->persist($comment);
        $entityManager->flush();

        return $this->renderShowModal($card);
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Comment $comment,
        EntityManagerInterface $entityManager,
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

        return $this->renderShowModal($card);
    }

    private function renderShowModal(Card $card): Response
    {
        return $this->render('@App/cards/_show_modal.html.twig', [
            'card' => $card,
            'timeline' => $this->activityService->getCardTimeline($card),
        ]);
    }
}
