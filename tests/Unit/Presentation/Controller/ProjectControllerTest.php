<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Board;
use App\Domain\Entity\Column;
use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Presentation\Controller\ProjectController;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class ProjectControllerTest extends TestCase
{
    private ProjectController $controller;
    private Stub&Environment $twig;
    private Stub&UrlGeneratorInterface $urlGenerator;
    private Container $container;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $this->controller = new ProjectController();

        $this->container = new Container();
        $this->container->set('twig', $this->twig);
        $this->container->set('router', $this->urlGenerator);
        $this->container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $this->container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($this->container);
    }

    public function testIndexRedirectsToFirstBoardWhenBoardsExist(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 5);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('findByProject')->willReturn([$board]);

        $this->urlGenerator->method('generate')->willReturn('/project/1/board/5');

        $response = $this->controller->index($project, $boardRepository);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/project/1/board/5', $response->getTargetUrl());
    }

    public function testIndexRendersNoBoardsPageWhenEmpty(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('findByProject')->willReturn([]);

        $this->twig->method('render')->willReturn('<html>no boards</html>');

        $response = $this->controller->index($project, $boardRepository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>no boards</html>', $response->getContent());
    }

    public function testIndexPassesProjectToNoBoardsTemplate(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('findByProject')->willReturn([]);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/project/no_boards.html.twig',
                ['project' => $project],
            )
            ->willReturn('<html>no boards</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->index($project, $boardRepository);
    }

    public function testBoardRendersBoardView(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 5);
        $board->setProject($project);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('find')->willReturn($board);
        $boardRepository->method('findByProject')->willReturn([$board]);

        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('findByBoard')->willReturn([]);

        $this->twig->method('render')->willReturn('<html>board</html>');

        $response = $this->controller->board(new Request(), $project, 5, $boardRepository, $cardRepository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>board</html>', $response->getContent());
    }

    public function testBoardThrowsNotFoundWhenBoardIsNull(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('find')->willReturn(null);

        $cardRepository = $this->createStub(CardRepository::class);

        $this->expectException(NotFoundHttpException::class);

        $this->controller->board(new Request(), $project, 999, $boardRepository, $cardRepository);
    }

    public function testBoardThrowsNotFoundWhenBoardBelongsToDifferentProject(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $otherProject = new Project();
        $this->setEntityId($otherProject, 2);

        $board = new Board();
        $this->setEntityId($board, 5);
        $board->setProject($otherProject);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('find')->willReturn($board);

        $cardRepository = $this->createStub(CardRepository::class);

        $this->expectException(NotFoundHttpException::class);

        $this->controller->board(new Request(), $project, 5, $boardRepository, $cardRepository);
    }

    public function testBoardGroupsCardsByColumn(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 5);
        $board->setProject($project);

        $column = new Column();
        $this->setEntityId($column, 10);
        $column->setBoard($board);

        $card = $this->createStub(\App\Domain\Entity\Card::class);
        $card->method('getColumn')->willReturn($column);

        $boardRepository = $this->createStub(BoardRepository::class);
        $boardRepository->method('find')->willReturn($board);
        $boardRepository->method('findByProject')->willReturn([$board]);

        $cardRepository = $this->createStub(CardRepository::class);
        $cardRepository->method('findByBoard')->willReturn([$card]);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/project/board.html.twig',
                self::callback(function (array $params) use ($board, $project, $card): bool {
                    return $params['project'] === $project
                        && $params['board'] === $board
                        && $params['boards'] === [$board]
                        && isset($params['cardsByColumn'][10])
                        && $params['cardsByColumn'][10] === [$card];
                }),
            )
            ->willReturn('<html>board</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->board(new Request(), $project, 5, $boardRepository, $cardRepository);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);
    }
}
