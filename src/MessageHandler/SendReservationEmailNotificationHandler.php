<?php
// src/MessageHandler/SendReservationEmailNotificationHandler.php
namespace App\MessageHandler;

use App\Message\SendReservationEmailNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendReservationEmailNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail
    ) {}

    public function __invoke(SendReservationEmailNotification $message): void
    {
        $email = (new Email())
            ->from('reservations@restaurant.com')
            ->to($this->adminEmail)
            ->subject('Nouvelle réservation !')
            ->html($this->createEmailTemplate($message));

        $this->mailer->send($email);
    }

    private function createEmailTemplate(SendReservationEmailNotification $message): string
    {
        return <<<HTML
            <p>Bonjour !</p>
            <p>Une nouvelle réservation a été effectuée pour le restaurant <strong>{$message->getRestaurantName()}</strong>.</p>
            <p>La réservation est prévue pour le <strong>{$message->getReservationDate()->format('Y-m-d H:i')}</strong> et concerne <strong>{$message->getGuests()}</strong> convives.</p>
            <p>Vous pouvez consulter les détails de la réservation dans l'interface d'administration.</p>
        HTML;
    }
}
