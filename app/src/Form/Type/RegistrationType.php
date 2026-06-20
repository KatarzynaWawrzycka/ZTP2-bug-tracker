<?php

/**
 * Registration Type.
 */

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RegistrationType.
 */
class RegistrationType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Form Builder Interface
     * @param array                $options Array
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,
                'type' => PasswordType::class,
                'invalid_message' => 'Passwords must match',
                'required' => true,
                'first_options' => [
                    'label' => 'Password',
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(min: 6, max: 64),
                    ],
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
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
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
