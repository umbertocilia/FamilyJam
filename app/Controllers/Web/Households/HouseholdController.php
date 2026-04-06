<?php

declare(strict_types=1);

namespace App\Controllers\Web\Households;

use App\Controllers\BaseController;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class HouseholdController extends BaseController
{
    public function index(): string
    {
        return $this->render('households/index', [
            'pageClass' => 'households-page',
            'pageTitle' => 'Households | FamilyJam',
            'households' => $this->currentUserId === null ? [] : service('householdManager')->listForUser($this->currentUserId),
        ]);
    }

    public function create(): string
    {
        return $this->render('households/create', [
            'pageClass' => 'households-page',
            'pageTitle' => 'Create Household | FamilyJam',
        ]);
    }

    public function store(): RedirectResponse
    {
        helper('ui');
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'name',
            'description',
            'avatar_path',
            'base_currency',
            'timezone',
            'locale',
            'simplify_debts',
            'chore_scoring_enabled',
        ]);

        if (! $this->validateData($payload, config('Validation')->householdCreate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $household = service('householdManager')->create($this->currentUserId, $payload);
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam] household create failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', ui_locale() === 'it' ? 'Impossibile creare la household. Controlla i log server e riprova.' : 'Unable to create the household. Check the server logs and try again.');
        }

        return redirect()
            ->to(route_url('households.dashboard', $household['slug']))
            ->with('success', ui_locale() === 'it' ? 'Household creata correttamente.' : 'Household created successfully.');
    }

    public function switcher(): string
    {
        return $this->render('households/switch', [
            'pageClass' => 'households-page',
            'pageTitle' => 'Switch Household | FamilyJam',
            'households' => $this->currentUserId === null ? [] : service('householdManager')->listForUser($this->currentUserId),
        ]);
    }

    public function switch(string $identifier): RedirectResponse
    {
        helper('ui');
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $membership = service('householdManager')->setCurrent($this->currentUserId, $identifier);

        if ($membership === null) {
            return redirect()->to(route_url('households.switcher'))->with('error', ui_locale() === 'it' ? 'Household non disponibile per l\'utente corrente.' : 'Household not available for the current user.');
        }

        return redirect()
            ->to(route_url('households.dashboard', $membership['household_slug']))
            ->with('success', ui_locale() === 'it' ? 'Household attiva aggiornata.' : 'Active household updated.');
    }

    public function dashboard(string $identifier): string
    {
        $context = $this->currentUserId === null ? null : service('householdDashboard')->householdContext($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        service('householdManager')->setCurrent($this->currentUserId, (string) $context['household']['slug']);

        return $this->render('households/dashboard', [
            'pageClass' => 'household-dashboard-page',
            'pageTitle' => $context['household']['name'] . ' | FamilyJam',
            'dashboardContext' => $context,
        ]);
    }

    public function settings(string $identifier): string
    {
        $context = $this->currentUserId === null ? null : service('householdManager')->householdSettings($this->currentUserId, $identifier);

        if ($context === null) {
            throw SecurityException::forDisallowedAction();
        }

        return $this->render('households/settings', [
            'pageClass' => 'households-page',
            'pageTitle' => 'Household Settings | FamilyJam',
            'householdContext' => $context,
        ]);
    }

    public function updateSettings(string $identifier): RedirectResponse
    {
        helper('ui');
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        $payload = $this->request->getPost([
            'name',
            'description',
            'avatar_path',
            'base_currency',
            'timezone',
            'locale',
            'simplify_debts',
            'chore_scoring_enabled',
        ]);

        if (! $this->validateData($payload, config('Validation')->householdUpdate)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $context = service('householdManager')->updateSettings($this->currentUserId, $identifier, $payload);

        if ($context === null) {
            return redirect()->to(route_url('households.index'))->with('error', ui_locale() === 'it' ? 'Household non disponibile.' : 'Household not available.');
        }

        return redirect()
            ->to(route_url('settings.index', $context['household']['slug']))
            ->with('success', ui_locale() === 'it' ? 'Impostazioni household aggiornate.' : 'Household settings updated.');
    }

    public function createExpenseGroup(string $identifier): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        helper('ui');

        try {
            $context = service('householdManager')->createExpenseGroup($this->currentUserId, $identifier, $this->request->getPost(['name', 'description', 'color', 'member_user_ids']));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        if ($context === null) {
            return redirect()->to(route_url('households.index'))->with('error', 'Household not available.');
        }

        return redirect()->to(route_url('settings.index', $context['household']['slug']))->with('success', ui_text('settings.expense_groups.created'));
    }

    public function updateExpenseGroup(string $identifier, int $groupId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        helper('ui');

        try {
            $context = service('householdManager')->updateExpenseGroup($this->currentUserId, $identifier, $groupId, $this->request->getPost(['name', 'description', 'color', 'member_user_ids']));
        } catch (DomainException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        if ($context === null) {
            return redirect()->to(route_url('households.index'))->with('error', 'Household not available.');
        }

        return redirect()->to(route_url('settings.index', $context['household']['slug']))->with('success', ui_text('settings.expense_groups.updated'));
    }

    public function deleteExpenseGroup(string $identifier, int $groupId): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'));
        }

        helper('ui');

        try {
            $context = service('householdManager')->deleteExpenseGroup($this->currentUserId, $identifier, $groupId);
        } catch (DomainException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        if ($context === null) {
            return redirect()->to(route_url('households.index'))->with('error', 'Household not available.');
        }

        return redirect()->to(route_url('settings.index', $context['household']['slug']))->with('success', ui_text('settings.expense_groups.deleted'));
    }
}
