<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateTime', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
            ])
            ->add('numberOfGuests', IntegerType::class, [
                'label' => 'Nombre de personnes',
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
        ]);
    }
}
