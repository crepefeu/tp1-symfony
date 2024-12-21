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
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
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
