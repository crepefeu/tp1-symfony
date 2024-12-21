<?php
namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Entity\Reservation;
use App\Message\SendReservationEmailNotification;

class ReservationNotificationService
{
    public function __construct(
        private HubInterface $hub,
        private MessageBusInterface $messageBus
    ) {}

    public function notifyNewReservation(Reservation $reservation): void
    {
        $update = new Update(
            "admin/reservations",
            json_encode([
                'type' => 'new_reservation',
                'restaurant' => $reservation->getRestaurant()->getName(),
                'date' => $reservation->getDateTime()->format('Y-m-d H:i'),
                'guests' => $reservation->getNumberOfGuests()
            ])
        );
        
        $this->hub->publish($update);
        
        // Async email notification
        $this->messageBus->dispatch(new SendReservationEmailNotification(
            $reservation->getId(),
            $reservation->getRestaurant()->getName(),
            $reservation->getDateTime(),
            $reservation->getNumberOfGuests()
        ));
    }
}