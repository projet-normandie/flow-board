<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Controller\Admin;

use App\Domain\Entity\Project;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use App\Presentation\Controller\Admin\ProjectController;
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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final class ProjectControllerTest extends TestCase
{
    private ProjectController $controller;
    private Stub&Environment $twig;
    private Stub&UrlGeneratorInterface $urlGenerator;
    private Stub&FormFactoryInterface $formFactory;
    private Stub&FlashBagInterface $flashBag;
    private Container $container;

    protected function setUp(): void
    {
        $this->twig = $this->createStub(Environment::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->formFactory = $this->createStub(FormFactoryInterface::class);
        $this->flashBag = $this->createStub(FlashBagInterface::class);

        $session = $this->createStub(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = new RequestStack();
        $mainRequest = new Request();
        $mainRequest->setSession($session);
        $requestStack->push($mainRequest);

        $this->controller = new ProjectController();

        $this->container = new Container();
        $this->container->set('twig', $this->twig);
        $this->container->set('router', $this->urlGenerator);
        $this->container->set('form.factory', $this->formFactory);
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

    public function testIndexRendersProjectList(): void
    {
        $projects = [new Project(), new Project()];

        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('findAll')->willReturn($projects);

        $this->twig->method('render')->willReturn('<html>projects</html>');

        $response = $this->controller->index($repository);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>projects</html>', $response->getContent());
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
                '@App/admin/projects/index.html.twig',
                ['projects' => $projects],
            )
            ->willReturn('<html>projects</html>');

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

    public function testNewRendersFormOnGet(): void
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($this->createStub(FormView::class));

        $this->formFactory->method('create')->willReturn($form);
        $this->twig->method('render')->willReturn('<html>new form</html>');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $response = $this->controller->new(new Request(), $entityManager);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>new form</html>', $response->getContent());
    }

    public function testEditRendersFormOnGet(): void
    {
        $project = new Project();

        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($this->createStub(FormView::class));

        $this->formFactory->method('create')->willReturn($form);
        $this->twig->method('render')->willReturn('<html>edit form</html>');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $response = $this->controller->edit(new Request(), $project, $entityManager);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>edit form</html>', $response->getContent());
    }

    public function testDeleteRemovesProjectWithValidCsrfToken(): void
    {
        $project = (new Project())->setName('Test');

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);
        $this->container->set('security.csrf.token_manager', $csrfTokenManager);
        $this->controller->setContainer($this->container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($project);
        $entityManager->expects(self::once())->method('flush');

        $this->urlGenerator->method('generate')->willReturn('/admin/projects');

        $request = new Request(request: ['_token' => 'valid-token']);
        $request->setMethod('POST');

        $response = $this->controller->delete($request, $project, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDeleteDoesNotRemoveWithInvalidCsrfToken(): void
    {
        $project = (new Project())->setName('Test');

        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);
        $this->container->set('security.csrf.token_manager', $csrfTokenManager);
        $this->controller->setContainer($this->container);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');

        $this->urlGenerator->method('generate')->willReturn('/admin/projects');

        $request = new Request(request: ['_token' => 'invalid-token']);
        $request->setMethod('POST');

        $response = $this->controller->delete($request, $project, $entityManager);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }
}
