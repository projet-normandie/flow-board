<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller\Admin;

use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Presentation\Controller\Admin\DashboardController;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class DashboardControllerTest extends TestCase
{
    private DashboardController $controller;
    private Stub&Environment $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);

        $this->controller = new DashboardController();

        $container = new Container();
        $container->set('twig', $this->twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($container);
    }

    public function testIndexRendersDashboard(): void
    {
        $this->twig->method('render')->willReturn('<html>dashboard</html>');

        $response = $this->controller->index(
            $this->createRepositoryStub(ProjectRepository::class, 3),
            $this->createRepositoryStub(BoardRepository::class, 2),
            $this->createRepositoryStub(ColumnRepository::class, 8),
            $this->createRepositoryStub(LabelRepository::class, 5),
            $this->createRepositoryStub(CardRepository::class, 12),
            $this->createRepositoryStub(UserRepository::class, 4),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>dashboard</html>', $response->getContent());
    }

    public function testIndexPassesCountsToTemplate(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/admin/dashboard.html.twig',
                [
                    'projectCount' => 3,
                    'boardCount' => 2,
                    'columnCount' => 8,
                    'labelCount' => 5,
                    'cardCount' => 12,
                    'userCount' => 4,
                ],
            )
            ->willReturn('<html>dashboard</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->index(
            $this->createRepositoryStub(ProjectRepository::class, 3),
            $this->createRepositoryStub(BoardRepository::class, 2),
            $this->createRepositoryStub(ColumnRepository::class, 8),
            $this->createRepositoryStub(LabelRepository::class, 5),
            $this->createRepositoryStub(CardRepository::class, 12),
            $this->createRepositoryStub(UserRepository::class, 4),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return Stub&T
     */
    private function createRepositoryStub(string $class, int $count): Stub
    {
        $stub = $this->createStub($class);
        $stub->method('count')->willReturn($count);

        return $stub;
    }
}
