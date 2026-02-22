<?php

declare(strict_types=1);

namespace App\Presentation\Form;

use App\Domain\Entity\Enum\JobTitle;
use App\Domain\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<User>
 */
class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'common.email',
                'required' => true,
            ])
            ->add('fullName', TextType::class, [
                'label' => 'user.full_name',
                'required' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'common.password',
                'mapped' => false,
                'required' => !$isEdit,
                'constraints' => $isEdit ? [] : [
                    new NotBlank(),
                    new Length(min: 8, max: 4096),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'user.roles',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('jobTitle', EnumType::class, [
                'class' => JobTitle::class,
                'label' => 'user.job_title',
                'required' => false,
                'placeholder' => 'user.placeholder.select',
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'user.enabled',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
