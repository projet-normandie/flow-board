<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    private const array SUPPORTED_LOCALES = ['en', 'fr'];

    #[Route('/locale/{locale}', name: 'app_switch_locale', methods: ['GET'])]
    public function switchLocale(string $locale, Request $request): Response
    {
        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $request->getSession()->set('_locale', $locale);
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?? '/');
    }
}
