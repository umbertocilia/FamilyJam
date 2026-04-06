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
        if (session()->get('auth.user_id') !== null && service('sessionAuth')->hasValidSession()) {
            return null;
        }

        service('sessionAuth')->logout();

        session()->set('auth.intended_url', (string) current_url(true));

        return redirect()->to(route_url('auth.login'))->with('warning', 'Autenticazione richiesta.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
