<?php

declare(strict_types=1);

namespace App\Controllers\Web\Auth;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Security\Exceptions\SecurityException;
use DomainException;

final class ProfileController extends BaseController
{
    public function edit(): string
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('profile/edit', [
            'pageClass' => 'profile-page',
            'pageTitle' => 'Profile | FamilyJam',
            'profile' => service('userProfile')->profile($this->currentUserId),
        ]);
    }

    public function update(): RedirectResponse
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        $payload = $this->request->getPost([
            'display_name',
            'first_name',
            'last_name',
            'avatar_path',
            'locale',
            'theme',
            'timezone',
            'email_notifications',
        ]);

        if (! $this->validateData($payload, config('Validation')->userProfileUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            service('userProfile')->update($this->currentUserId, $payload, $this->request->getFile('avatar_image'));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->to(route_url('profile.edit'))->with('success', ui_text('profile.updated'));
    }

    public function destroy(): RedirectResponse
    {
        if ($this->currentUserId === null) {
            throw SecurityException::forDisallowedAction();
        }

        helper('ui');

        $payload = $this->request->getPost([
            'current_password',
            'confirmation_phrase',
        ]);

        if (! $this->validateData($payload, config('Validation')->accountDelete)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $expectedPhrase = 'DELETE';

        if (strtoupper(trim((string) ($payload['confirmation_phrase'] ?? ''))) !== $expectedPhrase) {
            return redirect()->back()->withInput()->with('error', ui_text('profile.delete.confirm_invalid'));
        }

        try {
            service('accountErasure')->eraseSelf(
                $this->currentUserId,
                (string) ($payload['current_password'] ?? ''),
                service('request')->getLocale(),
            );
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        service('sessionAuth')->logout();

        return redirect()->to(route_url('home'))->with('success', ui_text('profile.delete.completed'));
    }
}
