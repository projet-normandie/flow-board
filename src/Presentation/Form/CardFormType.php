<?php

declare(strict_types=1);

namespace App\Presentation\Form;

use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Label;
use App\Domain\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Card>
 */
class CardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'card.title_field',
                'required' => true,
            ]);

        if ($options['quick_mode']) {
            return;
        }

        $builder
            ->add('description', TextareaType::class, [
                'label' => 'card.description',
                'required' => false,
                'attr' => [
                    'data-controller' => 'quill',
                    'data-quill-toolbar-value' => 'full',
                ],
            ]);

        if (!$options['edit_mode']) {
            $builder->add('column', EntityType::class, [
                'class' => Column::class,
                'label' => 'card.column',
                'query_builder' => fn ($repository) => $repository->createQueryBuilder('c')
                    ->orderBy('c.position', 'ASC'),
            ]);
        }

        $builder
            ->add('priority', EnumType::class, [
                'class' => CardPriority::class,
                'label' => 'card.priority',
                'required' => false,
                'placeholder' => 'card.placeholder.select',
                'attr' => ['data-controller' => 'priority-selector'],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'card.due_date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('labels', EntityType::class, [
                'class' => Label::class,
                'label' => 'card.labels',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'choice_attr' => fn (Label $label): array => ['data-color' => $label->getColor()],
                'attr' => ['data-controller' => 'tom-select'],
            ])
            ->add('assignees', EntityType::class, [
                'class' => User::class,
                'label' => 'card.assignees',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => ['data-controller' => 'tom-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
            'quick_mode' => false,
            'edit_mode' => false,
        ]);
    }
}
