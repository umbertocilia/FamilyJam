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
        if (session()->get('auth.user_id') === null) {
            return null;
        }

        if (! service('sessionAuth')->hasValidSession()) {
            service('sessionAuth')->logout();

            return null;
        }

        return redirect()->to(route_url('app.index'))->with('info', 'Sei gia autenticato.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
