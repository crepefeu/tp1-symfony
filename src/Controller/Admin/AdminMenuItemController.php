<?php

namespace App\Controller\Admin;

use App\Entity\MenuItem;
use App\Form\MenuItemType;
use App\Repository\MenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menu-item')]
#[IsGranted('ROLE_ADMIN')]
class AdminMenuItemController extends AbstractController
{
    #[Route('/', name: 'app_admin_menu_item_index', methods: ['GET'])]
    public function index(MenuItemRepository $menuItemRepository): Response
    {
        return $this->render('admin/menu_item/index.html.twig', [
            'menu_items' => $menuItemRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_menu_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $menuItem = new MenuItem();
        $form = $this->createForm(MenuItemType::class, $menuItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($menuItem);
            $entityManager->flush();

            $this->addFlash('success', 'Item de menu créé avec succès');
            return $this->redirectToRoute('app_admin_menu_item_index');
        }

        return $this->render('admin/menu_item/new.html.twig', [
            'menu_item' => $menuItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_menu_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MenuItem $menuItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MenuItemType::class, $menuItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Item de menu modifié avec succès');
            return $this->redirectToRoute('app_admin_menu_item_index');
        }

        return $this->render('admin/menu_item/edit.html.twig', [
            'menu_item' => $menuItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_menu_item_delete', methods: ['POST'])]
    public function delete(Request $request, MenuItem $menuItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menuItem->getId(), $request->request->get('_token'))) {
            $entityManager->remove($menuItem);
            $entityManager->flush();
            $this->addFlash('success', 'Item de menu supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_menu_item_index');
    }
}
