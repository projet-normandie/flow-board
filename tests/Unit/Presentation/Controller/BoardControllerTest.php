<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Board;
use App\Domain\Entity\Project;
use App\Presentation\Controller\BoardController;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class BoardControllerTest extends TestCase
{
    private BoardController $controller;
    private Stub&UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        $this->controller = new BoardController();

        $container = new Container();
        $container->set('router', $this->urlGenerator);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($container);
    }

    public function testIndexRedirectsToProjectBoard(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 5);
        $board->setProject($project);

        $this->urlGenerator->method('generate')->willReturn('/project/1/board/5');

        $response = $this->controller->index($board);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/project/1/board/5', $response->getTargetUrl());
    }

    public function testIndexReturnsMovedPermanently(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 5);
        $board->setProject($project);

        $this->urlGenerator->method('generate')->willReturn('/project/1/board/5');

        $response = $this->controller->index($board);

        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testIndexGeneratesCorrectRoute(): void
    {
        $project = new Project();
        $this->setEntityId($project, 1);

        $board = new Board();
        $this->setEntityId($board, 5);
        $board->setProject($project);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_project_board', ['id' => 1, 'boardId' => 5])
            ->willReturn('/project/1/board/5');

        $container = new Container();
        $container->set('router', $urlGenerator);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->index($board);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);
    }
}
