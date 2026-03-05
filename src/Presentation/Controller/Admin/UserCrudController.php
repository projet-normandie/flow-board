<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Entity\Enum\JobTitle;
use App\Domain\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $loginHistory = Action::new('loginHistory', 'Historique', 'fa fa-clock-rotate-left')
            ->linkToRoute('admin_user_login_history', fn (User $user) => ['id' => $user->getId()])
            ->addCssClass('btn btn-secondary');

        return $actions->add(Crud::PAGE_INDEX, $loginHistory);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('fullName', 'user.full_name');
        yield EmailField::new('email', 'common.email');
        yield ChoiceField::new('jobTitle', 'user.job_title')
            ->setChoices(JobTitle::cases())
            ->setRequired(false);
        yield BooleanField::new('enabled', 'user.enabled');
    }
}
