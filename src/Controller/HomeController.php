<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RestaurantRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    #[Route('/home', name: 'app_home')]
    public function index(RestaurantRepository $restaurantRepository): Response
    {
        // Get all the restaurants from the database

        return $this->render('home/index.html.twig', [
            'restaurants' => $restaurantRepository->findBy([], ['id' => 'DESC'], 6),
        ]);
    }
}
