<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;

final class HomeController extends BaseController
{
    public function index(): string
    {
        return $this->render('pages/home', [
            'pageClass' => 'landing-page',
            'pageTitle' => 'FamilyJam | Household OS',
            'compactAuth' => true,
            'inviteToken' => is_string($this->request->getGet('invite')) ? (string) $this->request->getGet('invite') : null,
        ]);
    }
}
