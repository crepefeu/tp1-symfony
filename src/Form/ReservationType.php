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
        $builder
            ->add('restaurant', EntityType::class, [
                'class' => Restaurant::class,
                'choice_label' => 'name',
                'disabled' => !$options['is_admin'], // Only enabled for admin
                'required' => true,
            ])
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
            ->add('restaurantTable', EntityType::class, [
                'class' => Table::class,
                'choice_label' => function(Table $table) {
                    return sprintf('Table %d (%d personnes)', $table->getNumber(), $table->getCapacity());
                },
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('t')
                        ->where('t.restaurant = :restaurant')
                        ->andWhere('t.active = true')
                        ->setParameter('restaurant', $options['restaurant'])
                        ->orderBy('t.number', 'ASC');
                },
                'placeholder' => 'Choisir une table',
                'required' => true,
            ])
            ->add('specialRequests', TextareaType::class, [
                'label' => 'Demandes spéciales',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'restaurant' => null,
            'is_admin' => false,
        ]);
    }
}
