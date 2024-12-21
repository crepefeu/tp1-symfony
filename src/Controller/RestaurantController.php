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
use App\Enum\ReservationStatus;
use App\Service\LoyaltyService;
use App\Repository\DiscountRepository;

#[Route('/restaurant')]
class RestaurantController extends AbstractController
{
    public function __construct(
        private LoyaltyService $loyaltyService
    ) {}

    #[Route('/', name: 'app_restaurant_index', methods: ['GET'])]
    public function index(Request $request, RestaurantRepository $restaurantRepository): Response
    {
        $search = $request->query->get('search');
        $rating = $request->query->get('rating');
        $sort = $request->query->get('sort', 'rating_desc');

        $queryBuilder = $restaurantRepository->createQueryBuilder('r');

        // Apply search filter with LOWER function for case-insensitive search
        if ($search) {
            $queryBuilder
                ->andWhere('LOWER(r.name) LIKE LOWER(:search) OR LOWER(r.description) LIKE LOWER(:search)')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Apply rating filter
        if ($rating) {
            $queryBuilder
                ->andWhere('r.averageRating >= :rating OR (
                    SELECT AVG(rev.rating) 
                    FROM App\Entity\Review rev 
                    WHERE rev.restaurant = r
                ) >= :rating')
                ->setParameter('rating', (float) $rating);
        }

        // Apply sorting
        switch ($sort) {
            case 'rating_asc':
                $queryBuilder->orderBy('r.averageRating', 'ASC');
                break;
            case 'rating_desc':
                $queryBuilder->orderBy('r.averageRating', 'DESC');
                break;
            case 'name_asc':
                $queryBuilder->orderBy('r.name', 'ASC');
                break;
            case 'name_desc':
                $queryBuilder->orderBy('r.name', 'DESC');
                break;
        }

        $restaurants = $queryBuilder->getQuery()->getResult();

        // Update average ratings before returning results
        foreach ($restaurants as $restaurant) {
            if ($restaurant->getAverageRating() === null) {
                $averageRating = $restaurant->getAverageRating(); // This calls the calculation method
                if ($averageRating !== null) {
                    $restaurant->setAverageRating($averageRating);
                    $restaurantRepository->getEntityManager()->persist($restaurant);
                }
            }
        }
        $restaurantRepository->getEntityManager()->flush();

        return $this->render('restaurant/index.html.twig', [
            'restaurants' => $restaurants,
        ]);
    }

    #[Route('/{id}', name: 'app_restaurant_show', methods: ['GET', 'POST'])]
    public function show(Request $request, RestaurantRepository $restaurantRepository, int $id, EntityManagerInterface $entityManager, DiscountRepository $discountRepository): Response
    {
        $restaurant = $restaurantRepository->find($id);
        
        if (!$restaurant) {
            throw $this->createNotFoundException('Restaurant not found');
        }

        $reservation = new Reservation();
        $reservation->setRestaurant($restaurant);

        $form = $this->createForm(ReservationType::class, $reservation, [
            'restaurant' => $restaurant,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check table capacity
            $selectedTable = $reservation->getRestaurantTable();
            $numberOfGuests = $reservation->getNumberOfGuests();
            
            if ($selectedTable && $numberOfGuests > $selectedTable->getCapacity()) {
                $this->addFlash('error', sprintf(
                    'Le nombre de personnes dépasse la capacité de la table sélectionnée (maximum %d personnes)',
                    $selectedTable->getCapacity()
                ));
                return $this->render('restaurant/details.html.twig', [
                    'restaurant' => $restaurant,
                    'reservation_form' => $form->createView(),
                ]);
            }

            // Handle discount code
            $discountCode = $form->get('discountCode')->getData() ?? 
                           $request->request->get('form')['discountCode'] ?? null;
            
            if ($discountCode) {
                $discount = $discountRepository->findOneBy([
                    'code' => $discountCode,
                    'restaurant' => $restaurant
                ]);
                
                if ($discount && $discount->getEndDate() >= new \DateTime()) {
                    $reservation->setDiscount($discount);
                }
            }

            $reservation->setCreatedAt(new \DateTime());
            $reservation->setStatus(ReservationStatus::PENDING);
            
            if ($this->getUser()) {
                $reservation->setUser($this->getUser());
            }
            
            $entityManager->persist($reservation);
            $entityManager->flush();

            // Add loyalty points if user is logged in
            if ($this->getUser()) {
                $this->loyaltyService->addPointsForReservation($reservation, $this->getUser());
                $this->addFlash('success', 'Réservation confirmée ! Vous avez gagné des points de fidélité.');
            } else {
                $this->addFlash('success', 'Votre réservation a été enregistrée.');
            }

            // Redirect to the reservation recap page
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('restaurant/details.html.twig', [
            'restaurant' => $restaurant,
            'reservation_form' => $form->createView(),
        ]);
    }
}
