<?php

namespace App\Service;

use App\Entity\LoyaltyTransaction;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LoyaltyService
{
    public const POINTS_PER_RESERVATION = 10;
    public const POINTS_PER_GUEST = 2;
    
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function addPointsForReservation(Reservation $reservation, User $user): void
    {
        $basePoints = self::POINTS_PER_RESERVATION;
        $guestPoints = $reservation->getNumberOfGuests() * self::POINTS_PER_GUEST;
        $totalPoints = $basePoints + $guestPoints;

        $transaction = new LoyaltyTransaction();
        $transaction->setUser($user)
            ->setPoints($totalPoints)
            ->setDescription(sprintf(
                'Réservation pour %d personne(s) au %s',
                $reservation->getNumberOfGuests(),
                $reservation->getRestaurant()->getName()
            ))
            ->setReservation($reservation);

        $user->setLoyaltyPoints($user->getLoyaltyPoints() + $totalPoints);
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function deductPoints(User $user, int $points, string $reason): void
    {
        $transaction = new LoyaltyTransaction();
        $transaction->setUser($user)
            ->setPoints(-$points)
            ->setDescription($reason);

        $user->setLoyaltyPoints($user->getLoyaltyPoints() - $points);
        
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function getUserPoints(User $user): int
    {
        return $user->getLoyaltyPoints();
    }
}
