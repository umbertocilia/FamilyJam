<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

final class LegalController extends BaseController
{
    public function privacy(): string
    {
        return $this->render('legal/privacy', [
            'pageClass' => 'legal-page',
            'pageTitle' => ui_text('gdpr.privacy.title') . ' | FamilyJam',
            'legalContext' => service('privacyConsent')->privacyDocumentContext(),
        ]);
    }

    public function cookies(): string
    {
        return $this->render('legal/cookies', [
            'pageClass' => 'legal-page',
            'pageTitle' => ui_text('gdpr.cookies.title') . ' | FamilyJam',
            'legalContext' => service('privacyConsent')->privacyDocumentContext(),
        ]);
    }

    public function essentialOnly(): RedirectResponse
    {
        $result = service('privacyConsent')->acceptEssential($this->currentUserId, service('request')->getLocale(), 'banner');
        $response = redirect()->to($this->safeReturnUrl());
        service('privacyConsent')->applyCookies($response, $result['consent_uuid'], $result['state']);

        return $response->with('success', ui_text('gdpr.flash.saved'));
    }

    public function acceptAll(): RedirectResponse
    {
        $result = service('privacyConsent')->acceptAll($this->currentUserId, service('request')->getLocale(), 'banner');
        $response = redirect()->to($this->safeReturnUrl());
        service('privacyConsent')->applyCookies($response, $result['consent_uuid'], $result['state']);

        return $response->with('success', ui_text('gdpr.flash.saved'));
    }

    public function savePreferences(): RedirectResponse
    {
        $payload = $this->request->getPost(['preferences', 'analytics', 'marketing']);
        $result = service('privacyConsent')->savePreferences($this->currentUserId, service('request')->getLocale(), $payload, 'preferences');
        $response = redirect()->to($this->safeReturnUrl());
        service('privacyConsent')->applyCookies($response, $result['consent_uuid'], $result['state']);

        return $response->with('success', ui_text('gdpr.flash.saved'));
    }

    private function safeReturnUrl(): string
    {
        $candidate = $this->request->getPost('return_url');
        if (! is_string($candidate) || $candidate === '') {
            return current_url();
        }

        $baseUrl = rtrim(base_url(), '/');
        if (str_starts_with($candidate, $baseUrl) || str_starts_with($candidate, '/')) {
            return $candidate;
        }

        return current_url();
    }
}
