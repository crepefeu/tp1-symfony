<?php
// src/Form/CategoryType.php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Restaurant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('restaurants', EntityType::class, [
                'class' => Restaurant::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,  // Change to true to use checkboxes
                'required' => false,
                'label' => 'Restaurants',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700'],
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500'];
                },
                'row_attr' => ['class' => 'grid grid-cols-1 gap-2'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}