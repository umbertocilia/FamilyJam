<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class GuestOnlyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $sessionAuth = service('sessionAuth');

        if (! $sessionAuth->hasValidSession()) {
            if (session()->get('auth.user_id') !== null) {
                $sessionAuth->logout(false);
            }

            return null;
        }

        return redirect()->to(route_url('app.index'))->with('info', 'Sei gia autenticato.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
