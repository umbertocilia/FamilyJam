<?php

namespace App\Controllers;

use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Auth\SessionAuthService;
use App\Services\Households\HouseholdContextService;
use App\Services\UI\AppShellService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Session\Session;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * @var list<string>
     */
    protected $helpers = ['form', 'text', 'url', 'flash', 'form_state', 'household', 'permission', 'expense', 'balance', 'recurring', 'chore', 'shopping', 'pinboard', 'notification', 'report', 'ui'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected Session $session;
    protected HouseholdContextService $householdContext;
    protected HouseholdAuthorizationService $householdAuthorization;
    protected SessionAuthService $sessionAuth;
    protected AppShellService $appShell;
    protected ?int $currentUserId = null;
    protected ?array $currentUser = null;
    protected ?array $activeHousehold = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        $this->session                = service('session');
        $this->householdContext       = service('householdContext');
        $this->householdAuthorization = service('householdAuthorization');
        $this->sessionAuth            = service('sessionAuth');
        $this->appShell               = service('appShell');
        $this->currentUserId          = $this->resolveCurrentUserId();
        $this->currentUser            = $this->sessionAuth->currentUser();
        $this->applyPreferredLocale();
        $this->activeHousehold        = $this->householdContext->activeHouseholdByIdentifier($this->resolveRequestedHouseholdIdentifier());
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): string
    {
        return view($view, array_merge([
            'activeHousehold' => $this->activeHousehold,
            'currentUserId'   => $this->currentUserId,
            'currentUser'     => $this->currentUser,
            'formErrors'      => $this->session->getFlashdata('errors') ?? [],
            'appShell'        => $this->appShell->sharedViewData($this->activeHousehold, $this->currentUserId),
        ], $data));
    }

    protected function resolveCurrentUserId(): ?int
    {
        $userId = $this->session->get('auth.user_id');

        return $userId === null ? null : (int) $userId;
    }

    protected function resolveRequestedHouseholdIdentifier(): ?string
    {
        $firstSegment = $this->request->getUri()->getSegment(1);

        if ($firstSegment === 'h') {
            $householdIdentifier = $this->request->getUri()->getSegment(2);

            return $householdIdentifier === '' ? null : $householdIdentifier;
        }

        if ($firstSegment !== 'households') {
            return null;
        }

        $householdIdentifier = $this->request->getUri()->getSegment(2);

        if ($householdIdentifier === '' || in_array($householdIdentifier, ['create', 'switch'], true)) {
            return null;
        }

        return $householdIdentifier;
    }

    protected function applyPreferredLocale(): void
    {
        $locale = $this->currentUser['locale'] ?? $this->session->get('app.locale') ?? config('App')->defaultLocale;
        $locale = is_string($locale) && in_array($locale, ['it', 'en'], true) ? $locale : config('App')->defaultLocale;

        $this->request->setLocale($locale);
        $this->session->set('app.locale', $locale);
    }
}
