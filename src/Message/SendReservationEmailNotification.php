<?php
// src/Message/SendReservationEmailNotification.php
namespace App\Message;

class SendReservationEmailNotification
{
    public function __construct(
        private int $reservationId,
        private string $restaurantName,
        private \DateTime $reservationDate,
        private int $guests
    ) {}

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getRestaurantName(): string
    {
        return $this->restaurantName;
    }

    public function getReservationDate(): \DateTime
    {
        return $this->reservationDate;
    }

    public function getGuests(): int
    {
        return $this->guests;
    }
}
