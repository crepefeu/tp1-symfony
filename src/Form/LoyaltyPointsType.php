<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoyaltyPointsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'choices' => [
                    'Ajouter' => 'add',
                    'Retirer' => 'remove',
                ],
                'label' => 'Action',
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points',
                'attr' => [
                    'min' => 1,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
