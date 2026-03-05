<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Infrastructure\Doctrine\Repository\BoardRepository;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\ProjectRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly BoardRepository $boardRepository,
        private readonly ColumnRepository $columnRepository,
        private readonly LabelRepository $labelRepository,
        private readonly CardRepository $cardRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function index(): Response
    {
        return $this->render('@App/admin/dashboard.html.twig', [
            'projectCount' => $this->projectRepository->count(),
            'boardCount' => $this->boardRepository->count(),
            'columnCount' => $this->columnRepository->count(),
            'labelCount' => $this->labelRepository->count(),
            'cardCount' => $this->cardRepository->count(),
            'userCount' => $this->userRepository->count(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('FlowBoard Admin')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('admin.dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('nav.back_to_site', 'fa fa-arrow-left', 'app_home');

        yield MenuItem::section('admin.section.content');
        yield MenuItem::linkTo(ProjectCrudController::class, 'nav.projects', 'fa fa-folder');
        yield MenuItem::linkTo(BoardCrudController::class, 'nav.boards', 'fa fa-table-columns');
        yield MenuItem::linkTo(ColumnCrudController::class, 'nav.columns', 'fa fa-bars');
        yield MenuItem::linkTo(CardCrudController::class, 'card.title', 'fa fa-credit-card');
        yield MenuItem::linkTo(LabelCrudController::class, 'nav.labels', 'fa fa-tag');

        yield MenuItem::section('admin.title');
        yield MenuItem::linkTo(UserCrudController::class, 'nav.users', 'fa fa-users')
            ->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToRoute('nav.login_history', 'fa fa-clock-rotate-left', 'admin_user_login_history_all')
            ->setPermission('ROLE_SUPER_ADMIN');
    }
}
