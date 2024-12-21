<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/review')]
#[IsGranted('ROLE_ADMIN')]
class AdminReviewController extends AbstractController
{
    #[Route('/', name: 'app_admin_review_index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): Response
    {
        return $this->render('admin/review/index.html.twig', [
            'reviews' => $reviewRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('admin/review/show.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->request->get('_token'))) {
            $entityManager->remove($review);
            $entityManager->flush();
            $this->addFlash('success', 'Avis supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_review_index');
    }

    #[Route('/{id}/approve', name: 'app_admin_review_approve', methods: ['POST'])]
    public function approve(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('approve'.$review->getId(), $request->request->get('_token'))) {
            $review->setIsApproved(true);
            $entityManager->flush();
            $this->addFlash('success', 'Avis approuvé avec succès');
        }

        return $this->redirectToRoute('app_admin_review_index');
    }

    #[Route('/{id}/disapprove', name: 'app_admin_review_disapprove', methods: ['POST'])]
    public function disapprove(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('disapprove'.$review->getId(), $request->request->get('_token'))) {
            $review->setIsApproved(false);
            $entityManager->flush();
            $this->addFlash('success', 'Avis désapprouvé avec succès');
        }

        return $this->redirectToRoute('app_admin_review_index');
    }
}
