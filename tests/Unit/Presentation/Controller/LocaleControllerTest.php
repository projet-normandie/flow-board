<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Presentation\Controller\LocaleController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class LocaleControllerTest extends TestCase
{
    private LocaleController $controller;

    protected function setUp(): void
    {
        $this->controller = new LocaleController();

        $container = new Container();
        $container->set('router', $this->createStub(UrlGeneratorInterface::class));
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($container);
    }

    public function testSwitchLocaleSetsSupportedLocaleInSession(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $this->controller->switchLocale('fr', $request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('fr', $request->getSession()->get('_locale'));
    }

    public function testSwitchLocaleIgnoresUnsupportedLocale(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $this->controller->switchLocale('de', $request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertNull($request->getSession()->get('_locale'));
    }

    public function testSwitchLocaleRedirectsToReferer(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->headers->set('referer', '/project/1/board/2');

        $response = $this->controller->switchLocale('en', $request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/project/1/board/2', $response->getTargetUrl());
    }

    public function testSwitchLocaleRedirectsToHomeWhenNoReferer(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $this->controller->switchLocale('fr', $request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getTargetUrl());
    }
}
