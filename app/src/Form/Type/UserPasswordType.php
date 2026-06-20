<?php

/**
 * User Password Type.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Class UserPasswordType.
 */
class UserPasswordType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Form Builder Interface
     * @param array                $options Array
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'first_options' => [
                'label' => 'New password',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'second_options' => [
                'label' => 'Repeat password',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'invalid_message' => 'Passwords do not match.',
            'constraints' => [
                new NotBlank(),
                new Length(
                    min: 5,
                    max: 100
                ),
            ],
        ]);
    }

    /**
     * Configure options.
     *
     * @param OptionsResolver $resolver Options Resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
