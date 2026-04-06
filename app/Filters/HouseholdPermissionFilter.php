<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

final class HouseholdPermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $permission = $arguments[0] ?? null;
        $userId = session()->get('auth.user_id');

        if ($userId === null) {
            return redirect()->to(route_url('auth.login'))->with('warning', 'Autenticazione richiesta.');
        }

        $householdSlug = $arguments[1] ?? $this->resolveHouseholdSlug($request);

        if ($permission === null || $householdSlug === null) {
            throw SecurityException::forDisallowedAction();
        }

        $authorization = service('householdAuthorization');

        if (! $authorization->canByIdentifier((int) $userId, (string) $householdSlug, (string) $permission)) {
            throw SecurityException::forDisallowedAction();
        }

        service('householdContext')->activeHouseholdByIdentifier((string) $householdSlug);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function resolveHouseholdSlug(RequestInterface $request): ?string
    {
        $householdSlug = $request->getGet('household');

        if (is_string($householdSlug) && $householdSlug !== '') {
            return $householdSlug;
        }

        if ($request->getUri()->getSegment(1) !== 'h') {
            if ($request->getUri()->getSegment(1) !== 'households') {
                return null;
            }

            $identifier = $request->getUri()->getSegment(2);

            if ($identifier === '' || in_array($identifier, ['create', 'switch'], true)) {
                return null;
            }

            return $identifier;
        }

        $householdSlug = $request->getUri()->getSegment(2);

        return $householdSlug === '' ? null : $householdSlug;
    }
}
