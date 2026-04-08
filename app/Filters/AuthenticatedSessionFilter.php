<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class AuthenticatedSessionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $sessionAuth = service('sessionAuth');

        if ($sessionAuth->hasValidSession()) {
            return null;
        }

        $sessionAuth->logout(false);

        session()->set('auth.intended_url', (string) current_url(true));

        return redirect()->to(route_url('auth.login'))->with('warning', 'Autenticazione richiesta.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
