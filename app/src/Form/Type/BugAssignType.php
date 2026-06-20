<?php

/**
 * Bug Assign Type.
 */

namespace App\Form\Type;

use App\Entity\Bug;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BugAssignType.
 */
class BugAssignType extends AbstractType
{
    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder Form Builder Interface
     * @param array                $options Array
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('assignedTo', EntityType::class, [
            'class' => User::class,
            'choices' => $options['admins'],
            'choice_label' => 'email',
            'placeholder' => 'Unassigned',
            'required' => false,
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
            'data_class' => Bug::class,
            'admins' => [],
        ]);
    }
}
