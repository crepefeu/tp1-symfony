<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Entity\Reservation;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RestaurantRepository;
use App\Entity\Table;

#[Route('/restaurant')]
class RestaurantController extends AbstractController
{
    #[Route('/', name: 'app_restaurant_index', methods: ['GET'])]
    public function index(RestaurantRepository $restaurantRepository): Response
    {
        return $this->render('restaurant/index.html.twig', [
            'restaurants' => $restaurantRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_restaurant_show', methods: ['GET', 'POST'])]
    public function show(Request $request, RestaurantRepository $restaurantRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $restaurant = $restaurantRepository->find($id);
        $reservation = new Reservation();
        $reservation->setRestaurant($restaurant);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant not found');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Find available table for the number of guests
            $availableTable = $entityManager->getRepository(Table::class)
                ->createQueryBuilder('t')
                ->where('t.restaurant = :restaurant')
                ->andWhere('t.capacity >= :guests')
                ->andWhere('t.id NOT IN (
                    SELECT IDENTITY(r.restaurantTable) 
                    FROM App\Entity\Reservation r 
                    WHERE r.dateTime = :dateTime 
                    AND r.status != :canceledStatus
                )')
                ->setParameter('restaurant', $restaurant)
                ->setParameter('guests', $reservation->getNumberOfGuests())
                ->setParameter('dateTime', $reservation->getDateTime())
                ->setParameter('canceledStatus', 'canceled')
                ->orderBy('t.capacity', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Or use the logger for persistent debug info
            dump($availableTable);

            if (!$availableTable) {
                $this->addFlash('error', 'Désolé, aucune table n\'est disponible pour cette date et ce nombre de personnes.');
                return $this->redirectToRoute('app_restaurant_show', ['id' => $restaurant->getId()]);
            }

            $reservation->setRestaurantTable($availableTable);
            $reservation->setStatus('pending');
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée.');
            return $this->redirectToRoute('app_restaurant_show', ['id' => $restaurant->getId()]);
        }

        return $this->render('restaurant/details.html.twig', [
            'restaurant' => $restaurant,
            'reservation_form' => $form->createView(),
        ]);
    }
}
