<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\LoyaltyTransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/loyalty')]
#[IsGranted('ROLE_USER')]
class LoyaltyController extends AbstractController
{
    #[Route('/', name: 'app_loyalty_index')]
    public function index(
        LoyaltyTransactionRepository $transactionRepository,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $transactions = $transactionRepository->getUserTransactions($user);

        return $this->render('loyalty/index.html.twig', [
            'transactions' => $transactions,
            'total_points' => $user->getLoyaltyPoints(),
        ]);
    }
}