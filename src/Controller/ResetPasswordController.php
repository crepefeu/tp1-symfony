<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token = null): Response
    {
        if ($token) {
            // Store the token in session and maintain the session
            $this->storeTokenInSession($token);
            
            // Redirect while maintaining the session
            return $this->redirectToRoute('app_reset_password', [], Response::HTTP_SEE_OTHER);
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            // Set plain password
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPlainPassword($plainPassword);

            // Force Doctrine to detect changes
            $this->entityManager->persist($user);

            // Flush changes - this will trigger the subscriber
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();
            
            $this->addFlash('success', 'Your password has been successfully reset. You can now log in with your new password.');
            
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        dump($user);

        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            dump($resetToken);
        } catch (TooManyPasswordRequestsException $e) {
            $this->addFlash('reset_password_error', sprintf(
                'Vous ne pouvez pas demander une autre réinitialisation de mot de passe pour le moment. Veuillez réessayer dans %d minutes.',
                ceil(($e->getAvailableAt()->getTimestamp() - time()) / 60)
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem handling your password reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        try {
            $email = (new TemplatedEmail())
                ->from(new Address('admin@example.com', 'Reset Password Bot'))
                ->to($user->getEmail())
                ->subject('Your password reset request')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'resetToken' => $resetToken,
                ])
                ->replyTo('noreply@example.com')
                ->priority(Email::PRIORITY_HIGH);

            dump('About to send email to: ' . $user->getEmail());
            $mailer->send($email);
            dump('Email sent successfully');
        } catch (\Exception $e) {
            dump('Email sending failed: ' . $e->getMessage());
            $this->addFlash('reset_password_error', 'There was a problem sending the password reset email - ' . $e->getMessage());
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // We prevent users from directly accessing this page
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }
}
