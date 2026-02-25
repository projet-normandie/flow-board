<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\User;
use App\Presentation\Form\ProfileInfoFormType;
use App\Presentation\Form\ProfilePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $infoForm = $this->createForm(ProfileInfoFormType::class, $user, [
            'action' => $this->generateUrl('app_profile_info'),
        ]);

        $passwordForm = $this->createForm(ProfilePasswordFormType::class, $user, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);

        return $this->render('@App/profile/index.html.twig', [
            'infoForm' => $infoForm,
            'passwordForm' => $passwordForm,
            'activeTab' => 'info',
        ]);
    }

    #[Route('/profile', name: 'app_profile_info', methods: ['POST'])]
    public function updateInfo(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $infoForm = $this->createForm(ProfileInfoFormType::class, $user, [
            'action' => $this->generateUrl('app_profile_info'),
        ]);
        $infoForm->handleRequest($request);

        if ($infoForm->isSubmitted() && $infoForm->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'profile.flash.info_updated');

            return $this->redirectToRoute('app_profile');
        }

        $passwordForm = $this->createForm(ProfilePasswordFormType::class, $user, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);

        return $this->render('@App/profile/index.html.twig', [
            'infoForm' => $infoForm,
            'passwordForm' => $passwordForm,
            'activeTab' => 'info',
        ]);
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $passwordForm = $this->createForm(ProfilePasswordFormType::class, $user, [
            'action' => $this->generateUrl('app_profile_password'),
        ]);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            /** @var string $currentPassword */
            $currentPassword = $passwordForm->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'profile.flash.wrong_password');

                return $this->redirectToRoute('app_profile', ['_fragment' => 'password']);
            }

            /** @var string $plainPassword */
            $plainPassword = $passwordForm->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->flush();

            $this->addFlash('success', 'profile.flash.password_updated');

            return $this->redirectToRoute('app_profile', ['_fragment' => 'password']);
        }

        $infoForm = $this->createForm(ProfileInfoFormType::class, $user, [
            'action' => $this->generateUrl('app_profile_info'),
        ]);

        return $this->render('@App/profile/index.html.twig', [
            'infoForm' => $infoForm,
            'passwordForm' => $passwordForm,
            'activeTab' => 'password',
        ]);
    }
}
