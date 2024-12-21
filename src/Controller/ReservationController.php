<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Service\LoyaltyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ReservationType;
use App\Entity\Restaurant;
use App\Enum\ReservationStatus;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoyaltyService $loyaltyService
    ) {}

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $restaurantId = $request->query->get('restaurant');
        $restaurant = $this->entityManager->getRepository(Restaurant::class)->find($restaurantId);
        
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
                return $this->render('reservation/new.html.twig', [
                    'form' => $form->createView(),
                    'restaurant' => $restaurant,
                ]);
            }

            $reservation->setCreatedAt(new \DateTime());
            $reservation->setStatus(ReservationStatus::PENDING);
            
            if ($this->getUser()) {
                $reservation->setUser($this->getUser());
            }
            
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();
    
            if ($this->getUser()) {
                $this->loyaltyService->addPointsForReservation($reservation, $this->getUser());
                $this->addFlash('success', 'Réservation confirmée ! Vous avez gagné des points de fidélité.');
            }
    
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }
    
        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'restaurant' => $restaurant,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(): Response
    {
        $reservations = $this->entityManager
            ->getRepository(Reservation::class)
            ->findAll();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
