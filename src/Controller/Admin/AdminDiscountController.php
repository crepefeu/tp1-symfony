<?php

namespace App\Controller\Admin;

use App\Entity\Discount;
use App\Form\DiscountType;
use App\Repository\DiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/discount', name: 'app_admin_discount_')]
#[IsGranted('ROLE_ADMIN')]
class AdminDiscountController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DiscountRepository $discountRepository): Response
    {
        return $this->render('admin/discount/index.html.twig', [
            'discounts' => $discountRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $discount = new Discount();
        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($discount);
            $entityManager->flush();

            $this->addFlash('success', 'Discount created successfully.');
            return $this->redirectToRoute('app_admin_discount_index');
        }

        return $this->render('admin/discount/new.html.twig', [
            'discount' => $discount,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Discount $discount): Response
    {
        return $this->render('admin/discount/show.html.twig', [
            'discount' => $discount,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Discount $discount, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Discount updated successfully.');
            return $this->redirectToRoute('app_admin_discount_index');
        }

        return $this->render('admin/discount/edit.html.twig', [
            'discount' => $discount,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Discount $discount, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$discount->getId(), $request->request->get('_token'))) {
            $entityManager->remove($discount);
            $entityManager->flush();
            
            $this->addFlash('success', 'Discount deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_discount_index');
    }
}