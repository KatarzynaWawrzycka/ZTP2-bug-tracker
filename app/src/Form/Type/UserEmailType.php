<?php

/**
 * User Email Type.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class USerEmailType.
 */
class UserEmailType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Form Builder Interface
     * @param array                $options Array
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Email',
            'mapped' => false,
            'constraints' => [
                new NotBlank(),
                new Email(),
            ],
            'attr' => [
                'autocomplete' => 'email',
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
