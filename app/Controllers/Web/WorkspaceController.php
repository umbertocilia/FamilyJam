<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

final class WorkspaceController extends BaseController
{
    public function index(): RedirectResponse|string
    {
        if ($this->activeHousehold !== null) {
            return redirect()->to(route_url('households.dashboard', $this->activeHousehold['household_slug']));
        }

        return redirect()->to(route_url('households.index'));
    }

    public function preview(): string
    {
        if ($this->currentUserId === null) {
            $preview = service('dashboardPreview')->workspaceData();

            return $this->render('pages/workspace_preview_public', [
                'pageClass' => 'workspace-preview-page',
                'pageTitle' => ($preview['household']['name'] ?? 'Workspace Preview') . ' | FamilyJam',
                'preview' => $preview,
                'compactAuth' => true,
            ]);
        }

        return $this->renderPreview();
    }

    public function dashboard(string $householdSlug): RedirectResponse
    {
        return redirect()->to(route_url('households.dashboard', $householdSlug));
    }

    public function households(): RedirectResponse
    {
        return redirect()->to(route_url('households.index'));
    }

    public function switchHousehold(string $householdSlug): RedirectResponse
    {
        if ($this->currentUserId === null) {
            return redirect()->to(route_url('auth.login'))->with('warning', 'Autenticazione richiesta.');
        }

        $membership = service('householdManager')->setCurrent($this->currentUserId, $householdSlug);

        if ($membership === null) {
            return redirect()->to(route_url('households.switcher'))->with('error', 'Household non disponibile.');
        }

        return redirect()
            ->to(route_url('households.dashboard', $membership['household_slug']))
            ->with('success', 'Active household updated.');
    }

    private function renderPreview(?string $householdSlug = null, ?array $activeHousehold = null): string
    {
        $preview = service('dashboardPreview')->workspaceData();

        if ($activeHousehold !== null) {
            $preview['household']['name'] = $activeHousehold['household_name'];
            $preview['household']['subtitle'] = 'Workspace household-aware preview';
        } elseif ($householdSlug !== null) {
            $preview['household']['name'] = $this->formatHouseholdName($householdSlug);
        }

        return $this->render('pages/workspace_preview', [
            'pageClass' => 'workspace-page',
            'pageTitle' => ($preview['household']['name'] ?? 'Workspace Preview') . ' | FamilyJam',
            'preview' => $preview,
            'isPreview' => $this->currentUserId === null,
        ]);
    }

    private function formatHouseholdName(string $householdSlug): string
    {
        return ucwords(str_replace('-', ' ', $householdSlug));
    }
}
