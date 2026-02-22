<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Domain\Entity\User;
use App\Presentation\Controller\SecurityController;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

final class SecurityControllerTest extends TestCase
{
    private SecurityController $controller;
    private Stub&AuthenticationUtils $authenticationUtils;
    private Stub&TokenStorageInterface $tokenStorage;
    private Stub&Environment $twig;
    private Stub&UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->authenticationUtils = $this->createStub(AuthenticationUtils::class);
        $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
        $this->twig = $this->createStub(Environment::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $this->controller = new SecurityController();

        $container = new Container();
        $container->set('security.token_storage', $this->tokenStorage);
        $container->set('twig', $this->twig);
        $container->set('router', $this->urlGenerator);
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($container);
    }

    public function testLoginRendersFormWhenNotAuthenticated(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->authenticationUtils->method('getLastUsername')
            ->willReturn('alice@flowboard.dev');
        $this->authenticationUtils->method('getLastAuthenticationError')
            ->willReturn(null);

        $this->twig->method('render')->willReturn('<html>login</html>');

        $response = $this->controller->login($this->authenticationUtils);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>login</html>', $response->getContent());
    }

    public function testLoginPassesLastUsernameAndErrorToTemplate(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);
        $error = new BadCredentialsException();

        $this->authenticationUtils->method('getLastUsername')
            ->willReturn('bad@example.com');
        $this->authenticationUtils->method('getLastAuthenticationError')
            ->willReturn($error);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/security/login.html.twig',
                [
                    'last_username' => 'bad@example.com',
                    'error' => $error,
                ],
            )
            ->willReturn('<html>login error</html>');

        $container = new Container();
        $container->set('security.token_storage', $this->tokenStorage);
        $container->set('twig', $twig);
        $container->set('router', $this->urlGenerator);
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $response = $this->controller->login($this->authenticationUtils);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>login error</html>', $response->getContent());
    }

    public function testLoginRedirectsWhenAlreadyAuthenticated(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new User());
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->urlGenerator->method('generate')->willReturn('/');

        $response = $this->controller->login($this->authenticationUtils);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getTargetUrl());
    }

    public function testLogoutThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $this->controller->logout();
    }
}
