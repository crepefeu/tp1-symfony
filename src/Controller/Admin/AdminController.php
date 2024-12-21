<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\RestaurantRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ReviewRepository;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        RestaurantRepository $restaurantRepository,
        ReservationRepository $reservationRepository,
        ReviewRepository $reviewRepository
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'users_count' => $userRepository->count([]),
            'restaurants_count' => $restaurantRepository->count([]),
            'reservations_count' => $reservationRepository->count([]),
            'reviews_count' => $reviewRepository->count([]),
            'recent_reservations' => $reservationRepository->findBy([], ['createdAt' => 'DESC'], 5),
            'recent_reviews' => $reviewRepository->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }

    #[Route('/users', name: 'user_index')]
    public function userIndex(): Response
    {
        return $this->render('admin/user/index.html.twig');
    }

    #[Route('/restaurants', name: 'restaurant_index')]
    public function restaurantIndex(): Response
    {
        return $this->render('admin/restaurant/index.html.twig');
    }

    #[Route('/reservations', name: 'reservation_index')]
    public function reservationIndex(): Response
    {
        return $this->render('admin/reservation/index.html.twig');
    }

    #[Route('/reviews', name: 'review_index')]
    public function reviewIndex(): Response
    {
        return $this->render('admin/review/index.html.twig');
    }
}
