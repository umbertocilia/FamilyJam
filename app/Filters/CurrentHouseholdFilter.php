<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class CurrentHouseholdFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $householdSlug = $this->resolveHouseholdIdentifier($request);

        if ($householdSlug === null) {
            return null;
        }

        $activeHousehold = service('householdContext')->activeHouseholdByIdentifier($householdSlug);

        if ($activeHousehold === null) {
            service('householdContext')->clearActiveHousehold();

            return redirect()
                ->to(route_url('households.index'))
                ->with('warning', 'La household richiesta non e disponibile per l\'utente corrente.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function resolveHouseholdIdentifier(RequestInterface $request): ?string
    {
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
