<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Enum\ReservationStatus;

#[Route('/admin/reservation')]
#[IsGranted('ROLE_ADMIN')]
class AdminReservationController extends AbstractController
{
    #[Route('/', name: 'app_admin_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation, [
            'restaurant' => null, // Allow restaurant selection in admin
            'is_admin' => true,   // Add this to your form type to handle admin-specific logic
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setCreatedAt(new \DateTime());
            $reservation->setStatus(ReservationStatus::CONFIRMED); // Admin-created reservations are confirmed by default
            
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation créée avec succès');
            return $this->redirectToRoute('app_admin_reservation_index');
        }

        return $this->render('admin/reservation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation, [
            'restaurant' => $reservation->getRestaurant(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation modifiée avec succès');
            return $this->redirectToRoute('app_admin_reservation_index');
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_admin_reservation_confirm', methods: ['POST'])]
    public function confirm(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('confirm'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus(ReservationStatus::CONFIRMED);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation confirmée avec succès');
        }

        return $this->redirectToRoute('app_admin_reservation_index');
    }

    #[Route('/{id}/cancel', name: 'app_admin_reservation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus(ReservationStatus::CANCELLED);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation annulée avec succès');
        }

        return $this->redirectToRoute('app_admin_reservation_index');
    }

    #[Route('/{id}', name: 'app_admin_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès');
        }

        return $this->redirectToRoute('app_admin_reservation_index');
    }
}
