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
use App\Repository\DiscountRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoyaltyService $loyaltyService,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route('/validate-discount', name: 'app_reservation_validate_discount', methods: ['POST'])]
    public function validateDiscount(Request $request, DiscountRepository $discountRepository): JsonResponse
    {
        $code = $request->request->get('code');
        $restaurantId = $request->request->get('restaurantId');
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('reservation_form', $token)) {
            return new JsonResponse(['valid' => false, 'message' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $discount = $discountRepository->findOneBy([
            'code' => $code,
            'restaurant' => $restaurantId
        ]);

        if (!$discount || $discount->getEndDate() < new \DateTime()) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Code promo invalide ou expiré'
            ]);
        }

        return new JsonResponse([
            'valid' => true,
            'discount' => [
                'id' => $discount->getId(),
                'code' => $discount->getCode(),
                'amount' => $discount->getDiscount(),
                'message' => sprintf('Code promo valide ! Réduction de %d%%', $discount->getDiscount())
            ]
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DiscountRepository $discountRepository): Response
    {
        // Get restaurant ID from query params or form data
        $restaurantId = $request->query->get('restaurant');
        
        if (!$restaurantId) {
            throw $this->createNotFoundException('Restaurant ID is required');
        }
        
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

        // Determine if we're coming from details page by checking referer
        $referer = $request->headers->get('referer', '');
        $isFromDetails = str_contains($referer, '/restaurant/');
        $template = $isFromDetails ? 'restaurant/details.html.twig' : 'reservation/new.html.twig';

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure the restaurant is set
            if (!$reservation->getRestaurant()) {
                $reservation->setRestaurant($restaurant);
            }
            
            // Check table capacity
            $selectedTable = $reservation->getRestaurantTable();
            $numberOfGuests = $reservation->getNumberOfGuests();

            dump($selectedTable);
            dump($numberOfGuests);
            
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

            dump("test");
            dump($form->get('discountCode')->getData());

            // Handle discount code - check both form data and request
            $discountCode = $form->get('discountCode')->getData() ?? 
                           $request->request->get('form')['discountCode'] ?? 
                           $request->request->get('discountCode');

            dump($discountCode);

            if ($discountCode) {
                $discount = $discountRepository->findOneBy([
                    'code' => $discountCode,
                    'restaurant' => $restaurant
                ]);
                
                if ($discount && $discount->getEndDate() >= new \DateTime()) {
                    $reservation->setDiscount($discount);
                } else {
                    $this->addFlash('error', 'Le code promo est invalide ou expiré');
                    return $this->render($template, [
                        'form' => $form->createView(),
                        'restaurant' => $restaurant,
                        'reservation_form' => $isFromDetails ? $form->createView() : null
                    ]);
                }
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

        $viewData = [
            'form' => $form->createView(),
            'restaurant' => $restaurant,
        ];

        if ($isFromDetails) {
            $viewData['reservation_form'] = $form->createView();
        }

        return $this->render($template, $viewData);
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
