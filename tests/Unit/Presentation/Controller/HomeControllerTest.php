<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller;

use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use App\Presentation\Controller\HomeController;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class HomeControllerTest extends TestCase
{
    private HomeController $controller;
    private Stub&Environment $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);

        $this->controller = new HomeController();

        $container = new Container();
        $container->set('twig', $this->twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($container);
    }

    public function testIndexRendersHomePage(): void
    {
        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('findAll')->willReturn([]);

        $this->twig->method('render')->willReturn('<html>home</html>');

        $response = $this->controller->index($repository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>home</html>', $response->getContent());
    }

    public function testIndexPassesProjectsToTemplate(): void
    {
        $projects = [new Project(), new Project()];

        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('findAll')->willReturn($projects);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/home/index.html.twig',
                ['projects' => $projects],
            )
            ->willReturn('<html>home</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->index($repository);
    }
}
