<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Config\Email;
use Throwable;

final class OutboundEmailService
{
    /**
     * @param array<string, mixed> $user
     */
    public function sendPasswordReset(array $user, string $rawToken): void
    {
        $this->deliver(
            (string) $user['email'],
            'FamilyJam - Reset password',
            "Ciao {$user['display_name']},\n\nApri questo link per reimpostare la password:\n" . site_url('reset-password/' . $rawToken),
        );
    }

    /**
     * @param array<string, mixed> $user
     */
    public function sendEmailVerification(array $user, string $rawToken): void
    {
        $this->deliver(
            (string) $user['email'],
            'FamilyJam - Verify your email',
            "Ciao {$user['display_name']},\n\nConferma il tuo indirizzo email da questo link:\n" . site_url('email/verify/' . $rawToken),
        );
    }

    /**
     * @param array<string, mixed> $invitation
     */
    public function sendInvitation(array $invitation, string $rawToken): void
    {
        $household = (string) ($invitation['household_name'] ?? 'FamilyJam household');
        $roleName = (string) ($invitation['role_name'] ?? 'Member');
        $message = trim((string) ($invitation['message'] ?? ''));

        $body = "Sei stato invitato a {$household} come {$roleName}.\n\n";

        if ($message !== '') {
            $body .= "Messaggio:\n{$message}\n\n";
        }

        $body .= "Accetta da qui:\n" . site_url('invitations/accept/' . $rawToken);

        $this->deliver((string) $invitation['email'], 'FamilyJam - Household invitation', $body);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $notification
     * @param array<string, mixed>|null $household
     */
    public function sendNotificationEmail(array $user, array $notification, ?array $household = null): void
    {
        $payload = [
            'user' => $user,
            'notification' => $notification,
            'household' => $household,
        ];

        $subject = 'FamilyJam - ' . (string) ($notification['title'] ?? 'Notifica');
        $text = view('emails/notifications/event_text', $payload);
        $html = view('emails/notifications/event_html', $payload);

        $this->deliverMessage((string) $user['email'], $subject, $text, $html);
    }

    private function deliver(string $recipient, string $subject, string $message): void
    {
        $this->deliverMessage($recipient, $subject, $message, null);
    }

    private function deliverMessage(string $recipient, string $subject, string $textMessage, ?string $htmlMessage): void
    {
        log_message('info', '[FamilyJam email scaffold] To: {to} | Subject: {subject} | Body: {body}', [
            'to' => $recipient,
            'subject' => $subject,
            'body' => $textMessage,
        ]);

        $config = config(Email::class);
        $fromEmail = trim((string) ($config->fromEmail ?? ''));

        if ($fromEmail === '') {
            log_message('warning', '[FamilyJam email scaffold] Email non inviata: fromEmail non configurata.');

            return;
        }

        if (($config->protocol ?? 'mail') === 'smtp') {
            $smtpHost = trim((string) ($config->SMTPHost ?? ''));
            $smtpUser = trim((string) ($config->SMTPUser ?? ''));
            $smtpPass = trim((string) ($config->SMTPPass ?? ''));

            if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
                log_message('warning', '[FamilyJam email scaffold] Email non inviata: configurazione SMTP incompleta.');

                return;
            }
        }

        try {
            $email = service('email');
            $email->setFrom($fromEmail, trim((string) ($config->fromName ?? 'FamilyJam')));
            $email->setTo($recipient);
            $email->setSubject($subject);
            $email->setMessage($htmlMessage ?? nl2br(htmlspecialchars($textMessage, ENT_QUOTES, 'UTF-8')));
            $email->setAltMessage($textMessage);
            $email->send();
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam email scaffold] Send failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
