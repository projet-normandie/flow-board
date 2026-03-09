<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Entity\Card;
use App\Domain\Entity\Enum\CardPriority;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<Card> */
class CardCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Card::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'card.title_field');
        yield TextEditorField::new('description', 'card.description')->hideOnIndex();
        yield ChoiceField::new('priority', 'card.priority')
            ->setChoices(CardPriority::cases())
            ->allowMultipleChoices(false)
            ->renderAsBadges()
            ->setRequired(false);
        yield DateField::new('dueDate', 'card.due_date')->setRequired(false);
        yield AssociationField::new('column', 'card.column')->hideOnForm();
        yield AssociationField::new('author', 'card.show.author');
        yield AssociationField::new('labels', 'card.labels')->hideOnIndex();
    }
}
