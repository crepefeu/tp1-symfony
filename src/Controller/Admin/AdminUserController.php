<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\LoyaltyTransaction;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // The HashPasswordSubscriber will automatically hash the password
            // when it detects that plainPassword is set
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle-ban', name: 'app_admin_user_toggle_ban', methods: ['POST'])]
    public function toggleBan(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_BANNED', $roles)) {
            $roles = array_diff($roles, ['ROLE_BANNED']);
        } else {
            $roles[] = 'ROLE_BANNED';
        }
        $user->setRoles($roles);

        $entityManager->flush();

        $this->addFlash('success', 'Statut de bannissement modifié avec succès');
        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                // Remove related loyalty transactions first
                $loyaltyTransactions = $entityManager->getRepository(LoyaltyTransaction::class)
                    ->findBy(['user' => $user]);
                foreach ($loyaltyTransactions as $transaction) {
                    $entityManager->remove($transaction);
                }
                
                $entityManager->remove($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'Utilisateur supprimé avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il possède des données liées');
            }
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/loyalty-points', name: 'app_admin_user_loyalty_points', methods: ['GET', 'POST'])]
    public function adjustLoyaltyPoints(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder()
            ->add('action', ChoiceType::class, [
                'choices' => [
                    'Ajouter' => 'add',
                    'Retirer' => 'remove',
                ],
                'label' => 'Action',
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Nombre de points',
                'attr' => [
                    'min' => 1,
                    'max' => 1000,
                    'step' => 1,
                    'value' => 50,
                    'class' => 'hidden', // Hide the number input
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Range([
                        'min' => 1,
                        'max' => 1000,
                    ]),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            if ($data['action'] === 'add') {
                $user->addLoyaltyPoints($data['points']);
                $this->addFlash('success', sprintf('%d points ajoutés avec succès.', $data['points']));
            } else {
                $user->removeLoyaltyPoints($data['points']);
                $this->addFlash('success', sprintf('%d points retirés avec succès.', $data['points']));
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/loyalty_points.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
