<?php
// src/Service/ReservationManager.php
namespace App\Service;

use App\Entity\Reservation;
use App\Message\SendReservationEmailNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ReservationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private HubInterface $hub
    ) {}

    public function createReservation(Reservation $reservation): void
    {
        // Persist the reservation
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        // Send email notification to the user
        $this->messageBus->dispatch(new SendReservationEmailNotification(
            $reservation->getId(),
            $reservation->getRestaurant()->getName(),
            $reservation->getDateTime(),
            $reservation->getNumberOfGuests()
        ));

        // Real-time notification
        $this->sendRealTimeNotification($reservation);
    }

    private function sendRealTimeNotification(Reservation $reservation): void
    {
        $update = new Update(
            'admin/reservations',
            json_encode([
                'type' => 'new_reservation',
                'data' => [
                    'id' => $reservation->getId(),
                    'restaurant' => $reservation->getRestaurant()->getName(),
                    'date' => $reservation->getDateTime()->format('Y-m-d H:i'),
                    'guests' => $reservation->getNumberOfGuests()
                ]
            ])
        );

        $this->hub->publish($update);
    }
}