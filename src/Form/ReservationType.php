<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Restaurant;
use App\Entity\Table;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $restaurant = $options['restaurant'] ?? null;
        
        // Add restaurant field as disabled but visible
        $builder->add('restaurant', EntityType::class, [
            'class' => Restaurant::class,
            'choice_label' => 'name',
            'data' => $restaurant,
            'disabled' => true,
            'attr' => [
                'class' => 'block w-full px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg shadow-sm'
            ]
        ]);

        // Add table field with restaurant-specific query
        $builder->add('restaurantTable', EntityType::class, [
            'class' => Table::class,
            'choice_label' => function(Table $table) {
                return sprintf('Table %d (%d personnes)', $table->getNumber(), $table->getCapacity());
            },
            'query_builder' => function (EntityRepository $er) use ($restaurant) {
                return $er->createQueryBuilder('t')
                    ->where('t.restaurant = :restaurant')
                    ->andWhere('t.active = true')
                    ->setParameter('restaurant', $restaurant)
                    ->orderBy('t.number', 'ASC');
            },
            'placeholder' => 'Choisir une table',
            'required' => true,
            'attr' => [
                'class' => 'block w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm'
            ]
        ]);

        $builder
            ->add('customerFirstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('customerLastName', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('customerPhone', TextType::class, [
                'label' => 'Téléphone',
            ])
            ->add('dateTime', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
            ])
            ->add('numberOfGuests', IntegerType::class, [
                'label' => 'Nombre de personnes',
            ])
            ->add('specialRequests', TextareaType::class, [
                'label' => 'Demandes spéciales',
                'required' => false,
            ])
            ->add('discountCode', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Code promo',
                'attr' => [
                    'placeholder' => 'Entrez votre code promo'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'restaurant' => null,
            'is_admin' => false,
        ]);

        $resolver->setAllowedTypes('restaurant', [Restaurant::class, 'null']);
    }
}
