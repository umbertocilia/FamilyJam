<?php

declare(strict_types=1);

namespace App\Controllers\Web\Auth;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class AuthController extends BaseController
{
    public function login(): string
    {
        helper('ui');
        $inviteToken = $this->request->getGet('invite') ?: $this->session->get('auth.pending_invitation_token');

        return $this->render('auth/login', [
            'pageClass' => 'auth-page',
            'pageTitle' => 'Login | FamilyJam',
            'authSubtitle' => ui_locale() === 'it' ? 'Accedi al workspace e alle tue household.' : 'Sign in to your workspace and households.',
            'inviteToken' => is_string($inviteToken) ? $inviteToken : null,
        ]);
    }

    public function loginSubmit(): RedirectResponse
    {
        helper('ui');
        $payload = $this->request->getPost([
            'email',
            'password',
            'invite_token',
            'remember',
        ]);

        if (! $this->validateData($payload, config('Validation')->authLogin)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $remember = in_array(strtolower(trim((string) ($payload['remember'] ?? '0'))), ['1', 'true', 'on', 'yes'], true);
        $user = service('sessionAuth')->attempt((string) $payload['email'], (string) $payload['password'], $remember);

        if ($user === null) {
            return redirect()->back()->withInput()->with('error', ui_locale() === 'it' ? 'Credenziali non valide o account temporaneamente bloccato.' : 'Invalid credentials or account temporarily blocked.');
        }

        $redirect = $this->consumeIntendedUrl();
        $inviteToken = trim((string) ($payload['invite_token'] ?? $this->session->get('auth.pending_invitation_token') ?? ''));

        if ($inviteToken !== '') {
            try {
                $membership = service('householdInvitation')->accept($inviteToken, (int) $user['id']);
                $this->session->remove('auth.pending_invitation_token');

                if ($membership !== null) {
                    return redirect()
                        ->to(route_url('households.dashboard', $membership['household_slug']))
                        ->withCookies()
                        ->with('success', ui_locale() === 'it' ? 'Invito accettato. Accesso completato.' : 'Invitation accepted. Sign-in completed.');
                }
            } catch (DomainException $exception) {
                return redirect()
                    ->to(route_url('invitations.accept', $inviteToken))
                    ->withCookies()
                    ->with('warning', $exception->getMessage());
            }
        }

        if ($redirect !== null) {
            return redirect()->to($redirect)->withCookies()->with('success', ui_locale() === 'it' ? 'Accesso completato.' : 'Signed in successfully.');
        }

        $activeHousehold = $this->householdContext->activeHousehold();

        if ($activeHousehold !== null) {
            return redirect()
                ->to(route_url('households.dashboard', $activeHousehold['household_slug']))
                ->withCookies()
                ->with('success', ui_locale() === 'it' ? 'Accesso completato.' : 'Signed in successfully.');
        }

        return redirect()->to(route_url('households.index'))->withCookies()->with('success', ui_locale() === 'it' ? 'Accesso completato.' : 'Signed in successfully.');
    }

    public function register(): string
    {
        $inviteToken = $this->request->getGet('invite') ?: $this->session->get('auth.pending_invitation_token');
        $invitationPreview = null;

        if (is_string($inviteToken) && $inviteToken !== '') {
            $invitationPreview = service('householdInvitation')->preview($inviteToken);
        }

        return $this->render('auth/register', [
            'pageClass' => 'auth-page',
            'pageTitle' => 'Register | FamilyJam',
            'authSubtitle' => 'Crea un account pronto per piu household.',
            'inviteToken' => is_string($inviteToken) ? $inviteToken : null,
            'invitationPreview' => $invitationPreview,
        ]);
    }

    public function registerSubmit(): RedirectResponse
    {
        $payload = $this->request->getPost([
            'email',
            'password',
            'password_confirmation',
            'display_name',
            'first_name',
            'last_name',
            'locale',
            'theme',
            'timezone',
            'email_notifications',
            'invite_token',
        ]);

        if (! $this->validateData($payload, config('Validation')->authRegister)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('registration')->register($payload);
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        $inviteToken = trim((string) ($payload['invite_token'] ?? ''));
        $redirectUrl = $inviteToken !== ''
            ? route_url('auth.login') . '?invite=' . urlencode($inviteToken)
            : route_url('auth.login');

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Account creato. Controlla la mail di verifica e poi accedi.');
    }

    public function forgotPassword(): string
    {
        return $this->render('auth/forgot_password', [
            'pageClass' => 'auth-page',
            'pageTitle' => 'Forgot Password | FamilyJam',
            'authSubtitle' => 'Richiedi il reset password con email verificata.',
        ]);
    }

    public function forgotPasswordSubmit(): RedirectResponse
    {
        $payload = $this->request->getPost(['email']);

        if (! $this->validateData($payload, config('Validation')->forgotPasswordRequest)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        service('passwordReset')->request((string) $payload['email']);

        return redirect()
            ->to(route_url('auth.forgot'))
            ->with('success', 'Se l\'account esiste, riceverai una mail con le istruzioni di reset.');
    }

    public function resetPassword(?string $token = null): RedirectResponse|string
    {
        if ($token === null || $token === '') {
            return redirect()->to(route_url('auth.forgot'));
        }

        $preview = service('passwordReset')->preview($token);

        return $this->render('auth/reset_password', [
            'pageClass' => 'auth-page',
            'pageTitle' => 'Reset Password | FamilyJam',
            'authSubtitle' => $preview === null
                ? 'Token di reset non valido o scaduto.'
                : 'Imposta una nuova password sicura per il tuo account.',
            'resetToken' => $token,
            'resetPreview' => $preview,
        ]);
    }

    public function resetPasswordSubmit(string $token): RedirectResponse
    {
        $payload = $this->request->getPost([
            'password',
            'password_confirmation',
        ]);

        if (! $this->validateData($payload, config('Validation')->authPasswordReset)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = service('passwordReset')->reset($token, (string) $payload['password']);

        if ($user === null) {
            return redirect()->to(route_url('auth.forgot'))->with('error', 'Token di reset non valido o scaduto.');
        }

        return redirect()
            ->to(route_url('auth.login'))
            ->with('success', 'Password aggiornata. Ora puoi accedere.');
    }

    public function verifyNotice(): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('auth/verify_email', [
            'pageClass' => 'auth-page',
            'pageTitle' => 'Verify Email | FamilyJam',
            'authSubtitle' => 'Conferma il tuo indirizzo email per completare il bootstrap auth.',
            'isVerified' => ! empty($this->currentUser['email_verified_at']),
        ]);
    }

    public function resendVerification(): RedirectResponse
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        if (! service('emailVerification')->resend($this->currentUserId)) {
            return redirect()->to(route_url('email.verify.notice'))->with('info', 'La mail e gia verificata oppure l\'utente non e disponibile.');
        }

        return redirect()->to(route_url('email.verify.notice'))->with('success', 'Mail di verifica reinviata.');
    }

    public function verifyEmailToken(string $token): RedirectResponse
    {
        $user = service('emailVerification')->verify($token);

        if ($user === null) {
            return redirect()->to(route_url('auth.login'))->with('error', 'Token di verifica non valido o scaduto.');
        }

        if ($this->currentUserId !== null) {
            return redirect()->to(route_url('email.verify.notice'))->with('success', 'Email verificata correttamente.');
        }

        return redirect()->to(route_url('auth.login'))->with('success', 'Email verificata. Ora puoi accedere.');
    }

    public function logout(): RedirectResponse
    {
        service('sessionAuth')->logout();

        return redirect()->to(route_url('home'))->withCookies()->with('info', 'Logout completato.');
    }

    private function consumeIntendedUrl(): ?string
    {
        $intended = $this->session->get('auth.intended_url');
        $this->session->remove('auth.intended_url');

        return is_string($intended) && $intended !== '' ? $intended : null;
    }
}
