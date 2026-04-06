<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class RateLimitLoginFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $email = strtolower(trim((string) $request->getPost('email')));
        $ipAddress = method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : 'cli';

        if ($email === '') {
            return null;
        }

        if (! service('loginThrottle')->blocked($email, $ipAddress)) {
            return null;
        }

        return redirect()->back()->withInput()->with('error', 'Troppi tentativi di login. Riprova tra qualche minuto.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
