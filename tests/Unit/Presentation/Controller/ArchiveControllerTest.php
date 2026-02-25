<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Card;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Presentation\Controller\ArchiveController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final class ArchiveControllerTest extends TestCase
{
    private ArchiveController $controller;
    private Stub&Environment $twig;
    private Stub&UrlGeneratorInterface $urlGenerator;
    private Stub&FlashBagInterface $flashBag;
    private Container $container;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->flashBag = $this->createStub(FlashBagInterface::class);

        $session = $this->createStub(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = new RequestStack();
        $mainRequest = new Request();
        $mainRequest->setSession($session);
        $requestStack->push($mainRequest);

        $this->controller = new ArchiveController();

        $this->container = new Container();
        $this->container->set('twig', $this->twig);
        $this->container->set('router', $this->urlGenerator);
        $this->container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $this->container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->container->set(
            'security.csrf.token_manager',
            $this->createStub(CsrfTokenManagerInterface::class),
        );
        $this->container->set('request_stack', $requestStack);

        $this->controller->setContainer($this->container);
    }

    public function testIndexRendersArchivedCards(): void
    {
        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('findArchivedCards')->willReturn([new Card(), new Card()]);

        $entityManager = $this->createStub(EntityManagerInterface::class);

        $this->twig->method('render')->willReturn('<html>archive</html>');

        $response = $this->controller->index($cardRepository, $entityManager);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>archive</html>', $response->getContent());
    }

    public function testIndexPassesCardsToTemplate(): void
    {
        $cards = [new Card(), new Card()];

        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('findArchivedCards')->willReturn($cards);

        $entityManager = $this->createStub(EntityManagerInterface::class);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/archive/index.html.twig',
                ['cards' => $cards],
            )
            ->willReturn('<html>archive</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->index($cardRepository, $entityManager);
    }

    public function testRestoreResetsDeletedAtWithValidCsrfToken(): void
    {
        $card = new Card();
        $this->setEntityId($card, 5);
        $card->setDeletedAt(new \DateTimeImmutable());

        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('find')->willReturn($card);

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);
        $this->container->set('security.csrf.token_manager', $csrfTokenManager);
        $this->controller->setContainer($this->container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getFilters')->willReturn($this->createFiltersStub(false));
        $entityManager->expects(self::once())->method('flush');

        $this->urlGenerator->method('generate')->willReturn('/archive');

        $request = new Request(request: ['_token' => 'valid-token']);
        $request->setMethod('POST');

        $response = $this->controller->restore($request, 5, $cardRepository, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertNull($card->getDeletedAt());
    }

    public function testRestoreDoesNothingWithInvalidCsrfToken(): void
    {
        $card = new Card();
        $this->setEntityId($card, 5);
        $deletedAt = new \DateTimeImmutable();
        $card->setDeletedAt($deletedAt);

        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('find')->willReturn($card);

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->container->set('security.csrf.token_manager', $csrfTokenManager);
        $this->controller->setContainer($this->container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getFilters')->willReturn($this->createFiltersStub(false));
        $entityManager->expects(self::never())->method('flush');

        $this->urlGenerator->method('generate')->willReturn('/archive');

        $request = new Request(request: ['_token' => 'invalid-token']);
        $request->setMethod('POST');

        $response = $this->controller->restore($request, 5, $cardRepository, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($deletedAt, $card->getDeletedAt());
    }

    public function testRestoreRedirectsToArchiveIndex(): void
    {
        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('find')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getFilters')->willReturn($this->createFiltersStub(false));

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_archive_index')
            ->willReturn('/archive');
        $this->container->set('router', $urlGenerator);
        $this->controller->setContainer($this->container);

        $request = new Request(request: ['_token' => 'any-token']);
        $request->setMethod('POST');

        $response = $this->controller->restore($request, 999, $cardRepository, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/archive', $response->getTargetUrl());
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);
    }

    private function createFiltersStub(bool $enabled): Stub&\Doctrine\ORM\Query\FilterCollection
    {
        $filters = $this->createStub(\Doctrine\ORM\Query\FilterCollection::class);
        $filters->method('isEnabled')->willReturn($enabled);

        return $filters;
    }
}
