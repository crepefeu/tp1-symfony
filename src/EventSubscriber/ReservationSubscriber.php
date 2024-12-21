<?php

namespace App\EventSubscriber;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ReservationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HubInterface $hub
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Reservation) {
            return;
        }

        $update = new Update(
            'restaurant/reservation',
            json_encode([
                'type' => 'reservation',
                'data' => [
                    'id' => $entity->getId(),
                    'restaurant' => $entity->getRestaurant()->getName(),
                    'date' => $entity->getDateTime()->format('Y-m-d H:i'),
                    'guests' => $entity->getNumberOfGuests()
                ]
            ])
        );

        $this->hub->publish($update);
    }
}