<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\LoyaltyTransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('/', name: 'app_account')]
    public function index(
        Request $request,
        LoyaltyTransactionRepository $transactionRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $user->setPlainPassword($plainPassword);
                // Explicitly tell Doctrine this entity was modified
                $entityManager->persist($user);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/index.html.twig', [
            'loyalty_transactions' => $transactionRepository->getUserTransactions($user),
            'total_points' => $user->getLoyaltyPoints(),
            'reservations' => $user->getReservations(),
            'form' => $form->createView(),
        ]);
    }
}
