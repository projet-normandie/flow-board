<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller\Admin;

use App\Domain\Entity\LoginHistory;
use App\Domain\Entity\User;
use App\Infrastructure\Doctrine\Repository\LoginHistoryRepository;
use App\Presentation\Controller\Admin\UserController;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class UserControllerTest extends TestCase
{
    private UserController $controller;
    private Stub&Environment $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);

        $this->controller = new UserController();

        $container = new Container();
        $container->set('twig', $this->twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );

        $this->controller->setContainer($container);
    }

    public function testLoginHistoryAllRendersResponse(): void
    {
        $histories = [$this->createStub(LoginHistory::class)];

        $repository = $this->createStub(LoginHistoryRepository::class);
        $repository->method('findLatest')->willReturn($histories);

        $this->twig->method('render')->willReturn('<html>history</html>');

        $response = $this->controller->loginHistoryAll($repository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>history</html>', $response->getContent());
    }

    public function testLoginHistoryAllPassesHistoriesToTemplate(): void
    {
        $histories = [$this->createStub(LoginHistory::class)];

        $repository = $this->createStub(LoginHistoryRepository::class);
        $repository->method('findLatest')->willReturn($histories);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/admin/users/login_history_all.html.twig',
                ['loginHistories' => $histories],
            )
            ->willReturn('<html>history</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->loginHistoryAll($repository);
    }

    public function testLoginHistoryRendersResponse(): void
    {
        $user = new User();
        $histories = [$this->createStub(LoginHistory::class)];

        $repository = $this->createStub(LoginHistoryRepository::class);
        $repository->method('findByUser')->willReturn($histories);

        $this->twig->method('render')->willReturn('<html>user history</html>');

        $response = $this->controller->loginHistory($user, $repository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>user history</html>', $response->getContent());
    }

    public function testLoginHistoryPassesDataToTemplate(): void
    {
        $user = new User();
        $histories = [$this->createStub(LoginHistory::class)];

        $repository = $this->createStub(LoginHistoryRepository::class);
        $repository->method('findByUser')->willReturn($histories);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@App/admin/users/login_history.html.twig',
                ['user' => $user, 'loginHistories' => $histories],
            )
            ->willReturn('<html>user history</html>');

        $container = new Container();
        $container->set('twig', $twig);
        $container->set('security.token_storage', $this->createStub(TokenStorageInterface::class));
        $container->set(
            'security.authorization_checker',
            $this->createStub(AuthorizationCheckerInterface::class),
        );
        $this->controller->setContainer($container);

        $this->controller->loginHistory($user, $repository);
    }
}
