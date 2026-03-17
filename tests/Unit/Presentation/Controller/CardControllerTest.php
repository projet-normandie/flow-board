<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Board;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Application\Service\ActivityService;
use App\Presentation\Controller\CardController;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final class CardControllerTest extends TestCase
{
    private CardController $controller;
    private Stub&Environment $twig;
    private Stub&UrlGeneratorInterface $urlGenerator;
    private Stub&FormFactoryInterface $formFactory;
    private Stub&FlashBagInterface $flashBag;
    private Stub&AuthorizationCheckerInterface $authorizationChecker;
    private Stub&CsrfTokenManagerInterface $csrfTokenManager;
    private Container $container;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->formFactory = $this->createStub(FormFactoryInterface::class);
        $this->flashBag = $this->createStub(FlashBagInterface::class);
        $this->authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $this->authorizationChecker->method('isGranted')->willReturn(true);
        $this->csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);

        $session = $this->createStub(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = new RequestStack();
        $mainRequest = new Request();
        $mainRequest->setSession($session);
        $requestStack->push($mainRequest);

        $user = new User();
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->controller = new CardController();

        $this->container = new Container();
        $this->container->set('twig', $this->twig);
        $this->container->set('router', $this->urlGenerator);
        $this->container->set('form.factory', $this->formFactory);
        $this->container->set('security.token_storage', $tokenStorage);
        $this->container->set('security.authorization_checker', $this->authorizationChecker);
        $this->container->set('security.csrf.token_manager', $this->csrfTokenManager);
        $this->container->set('request_stack', $requestStack);

        $this->controller->setContainer($this->container);
    }

    // -- new() ----------------------------------------------------------------

    public function testNewRendersFormOnGet(): void
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($this->createStub(FormView::class));

        $this->formFactory->method('create')->willReturn($form);
        $this->twig->method('render')->willReturn('<html>new form</html>');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $cardRepository = $this->createStub(CardRepository::class);

        $response = $this->controller->new(new Request(), $entityManager, $cardRepository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>new form</html>', $response->getContent());
    }

    public function testNewCreatesCardOnValidSubmit(): void
    {
        $column = $this->createColumnWithBoardProject();

        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $formFactory = $this->createStub(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(
            function (string $type, Card $card) use ($form, $column): FormInterface {
                $card->setColumn($column);
                return $form;
            },
        );
        $this->container->set('form.factory', $formFactory);
        $this->controller->setContainer($this->container);

        $this->urlGenerator->method('generate')->willReturn('/project/1/board/2');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('getNextPositionInColumn')->willReturn(1000);

        $response = $this->controller->new(new Request(), $entityManager, $cardRepository);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testNewPassesCardAndFormToTemplate(): void
    {
        $formView = $this->createStub(FormView::class);

        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $this->formFactory->method('create')->willReturn($form);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/cards/new.html.twig',
                self::callback(function (array $params) use ($formView): bool {
                    return $params['card'] instanceof Card
                        && $params['form'] === $formView;
                }),
            )
            ->willReturn('<html>new form</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('form.factory', $this->formFactory);
        $container->set('security.token_storage', $this->container->get('security.token_storage'));
        $container->set('security.authorization_checker', $this->authorizationChecker);
        $container->set('request_stack', $this->container->get('request_stack'));
        $this->controller->setContainer($container);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $cardRepository = $this->createStub(CardRepository::class);

        $this->controller->new(new Request(), $entityManager, $cardRepository);
    }

    // -- show() ---------------------------------------------------------------

    public function testShowRendersModal(): void
    {
        $card = $this->createCardWithColumnBoardProject();

        $this->twig->method('render')->willReturn('<html>show modal</html>');

        $activityService = $this->createStub(ActivityService::class);
        $activityService->method('getCardTimeline')->willReturn([]);

        $labelRepository = $this->createStub(LabelRepository::class);
        $labelRepository->method('findAll')->willReturn([]);
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findAll')->willReturn([]);

        $response = $this->controller->show($card, $activityService, $labelRepository, $userRepository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>show modal</html>', $response->getContent());
    }

    // -- delete() -------------------------------------------------------------

    public function testDeleteRemovesCardWithValidCsrfToken(): void
    {
        $card = $this->createCardWithColumnBoardProject();

        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);
        $this->container->set('security.csrf.token_manager', $this->csrfTokenManager);
        $this->controller->setContainer($this->container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($card);
        $entityManager->expects(self::once())->method('flush');

        $this->urlGenerator->method('generate')->willReturn('/project/1/board/2');

        $request = new Request(request: ['_token' => 'valid-token']);
        $request->setMethod('POST');

        $response = $this->controller->delete($request, $card, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteDoesNotRemoveWithInvalidCsrfToken(): void
    {
        $card = $this->createCardWithColumnBoardProject();

        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->container->set('security.csrf.token_manager', $this->csrfTokenManager);
        $this->controller->setContainer($this->container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');

        $this->urlGenerator->method('generate')->willReturn('/project/1/board/2');

        $request = new Request(request: ['_token' => 'invalid-token']);
        $request->setMethod('POST');

        $response = $this->controller->delete($request, $card, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteRedirectsToProjectBoard(): void
    {
        $card = $this->createCardWithColumnBoardProject();

        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->container->set('security.csrf.token_manager', $this->csrfTokenManager);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_project_board', self::anything())
            ->willReturn('/project/1/board/2');
        $this->container->set('router', $urlGenerator);

        $this->controller->setContainer($this->container);

        $entityManager = $this->createStub(EntityManagerInterface::class);

        $request = new Request(request: ['_token' => 'any-token']);
        $request->setMethod('POST');

        $response = $this->controller->delete($request, $card, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/project/1/board/2', $response->getTargetUrl());
    }

    // -- move() ---------------------------------------------------------------

    public function testMoveUpdatesCardColumnAndPosition(): void
    {
        $card = $this->createCardWithColumnBoardProject();
        $newColumn = new Column();

        $columnRepository = $this->createStub(ColumnRepository::class);
        $columnRepository->method('find')->willReturn($newColumn);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $request = new Request(content: '{"columnId":5,"position":2000}');
        $request->setMethod('POST');

        $response = $this->controller->move($request, $card, $entityManager, $columnRepository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('{"success":true}', $response->getContent());
        self::assertSame($newColumn, $card->getColumn());
        self::assertSame(2000, $card->getPosition());
    }

    public function testMoveReturnsBadRequestWhenMissingData(): void
    {
        $card = $this->createCardWithColumnBoardProject();
        $columnRepository = $this->createStub(ColumnRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $request = new Request(content: '{"columnId":5}');
        $request->setMethod('POST');

        $response = $this->controller->move($request, $card, $entityManager, $columnRepository);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testMoveReturnsNotFoundForInvalidColumn(): void
    {
        $card = $this->createCardWithColumnBoardProject();

        $columnRepository = $this->createStub(ColumnRepository::class);
        $columnRepository->method('find')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);

        $request = new Request(content: '{"columnId":999,"position":1000}');
        $request->setMethod('POST');

        $response = $this->controller->move($request, $card, $entityManager, $columnRepository);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testMoveReturnsBadRequestOnEmptyBody(): void
    {
        $card = $this->createCardWithColumnBoardProject();
        $columnRepository = $this->createStub(ColumnRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $request = new Request(content: '');
        $request->setMethod('POST');

        $response = $this->controller->move($request, $card, $entityManager, $columnRepository);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // -- helpers --------------------------------------------------------------

    private function createCardWithColumnBoardProject(): Card
    {
        $column = $this->createColumnWithBoardProject();

        $card = new Card();
        $this->setEntityId($card, 10);
        $card->setColumn($column);

        return $card;
    }

    private function createColumnWithBoardProject(): Column
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 2);
        $board->setProject($project);

        $column = new Column();
        $this->setEntityId($column, 3);
        $column->setBoard($board);

        return $column;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);
    }
}
