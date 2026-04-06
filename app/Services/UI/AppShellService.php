<?php

declare(strict_types=1);

namespace App\Services\UI;

use App\Authorization\Permission;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Households\HouseholdContextService;
use App\Services\Notifications\NotificationService;

final class AppShellService
{
    public function __construct(
        private readonly ?HouseholdContextService $householdContext = null,
        private readonly ?HouseholdAuthorizationService $householdAuthorizationService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * @param array<string, mixed>|null $activeHousehold
     * @return array<string, mixed>
     */
    public function sharedViewData(?array $activeHousehold, ?int $currentUserId): array
    {
        helper('ui');
        $householdSlug = isset($activeHousehold['household_slug']) && is_string($activeHousehold['household_slug'])
            ? $activeHousehold['household_slug']
            : null;
        $householdId = isset($activeHousehold['household_id']) && is_numeric($activeHousehold['household_id'])
            ? (int) $activeHousehold['household_id']
            : null;
        $requestPath = trim((string) service('request')->getUri()->getPath(), '/');
        $households = $currentUserId === null ? [] : ($this->householdContext ?? service('householdContext'))->availableHouseholds($currentUserId);
        $fallbackHref = $currentUserId === null ? route_url('home') : route_url('households.index');

        return [
            'brand' => [
                'name' => 'FamilyJam',
                'tagline' => ui_locale() === 'it' ? 'Sistema casa' : 'Household OS',
            ],
            'sidebarNavigation' => $this->navigationForSidebar($householdSlug, $requestPath, $fallbackHref, $currentUserId),
            'utilityNavigation' => $this->navigationForUtilities($householdSlug, $requestPath, $fallbackHref, $currentUserId),
            'householdSwitcher' => [
                'active' => $activeHousehold,
                'available' => array_map(
                    static fn (array $membership): array => [
                        'name' => $membership['household_name'],
                        'slug' => $membership['household_slug'],
                        'roles' => $membership['role_codes'] ?? '',
                        'isCurrent' => ($activeHousehold['household_slug'] ?? null) === $membership['household_slug'],
                    ],
                    $households,
                ),
            ],
            'uiNotifications' => $currentUserId === null
                ? $this->placeholderNotifications($activeHousehold)
                : ($this->notificationService ?? service('notificationService'))
                    ->drawerContext($currentUserId, $householdId, $householdSlug),
            'authLinks' => [
                ['label' => ui_text('shell.log_in'), 'href' => route_url('auth.login')],
                ['label' => ui_text('shell.create_account'), 'href' => route_url('auth.register')],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function moduleCatalog(?string $householdSlug = null): array
    {
        return [
            'memberships' => $this->module(
                key: 'memberships',
                title: 'Memberships & Invitations',
                summary: 'Manage members, invitation lifecycle, assigned roles and membership status inside the current tenant.',
                milestone: 'Invites, acceptance, revoke flow and household-scoped member listing.',
                highlights: ['household_memberships', 'invitations', 'membership_roles', 'audit log'],
                href: $this->tenantRoute($householdSlug, 'memberships.index'),
            ),
            'invitations' => $this->module(
                key: 'invitations',
                title: 'Invitations',
                summary: 'Invitation lifecycle with token hash, expiration and household-side revoke controls.',
                milestone: 'Invitation creation, reminders, accept flow and token validation.',
                highlights: ['token hash', 'expires_at', 'revoked_at', 'invited_by_user_id'],
                href: $this->tenantRoute($householdSlug, 'invitations.index'),
            ),
            'roles' => $this->module(
                key: 'roles',
                title: 'Roles & Permissions',
                summary: 'Household-level RBAC with custom roles, granular permissions and membership-role mapping.',
                milestone: 'Custom role CRUD, permission assignment and role matrix UI.',
                highlights: ['roles', 'permissions', 'role_permissions', 'membership_roles'],
                href: $this->tenantRoute($householdSlug, 'roles.index'),
            ),
            'expenses' => $this->module(
                key: 'expenses',
                title: 'Expenses',
                summary: 'Shared multi-payer expenses with equal, exact, percentage and shares split methods.',
                milestone: 'Create/update expenses, receipt attachments and ledger queries.',
                highlights: ['expenses', 'expense_payers', 'expense_splits', 'expense_categories'],
                href: $this->tenantRoute($householdSlug, 'expenses.index'),
            ),
            'settlements' => $this->module(
                key: 'settlements',
                title: 'Balances & Settlements',
                summary: 'Household ledger, net balances and manual settle up with audit tracing.',
                milestone: 'Balance calculation, optional simplification and settlement recording.',
                highlights: ['settlements', 'ledger queries', 'simplify_debts'],
                href: $this->tenantRoute($householdSlug, 'balances.overview'),
            ),
            'recurring' => $this->module(
                key: 'recurring',
                title: 'Recurring Rules',
                summary: 'Shared recurrence engine for expenses and chores, ready for Spark cron jobs.',
                milestone: 'Rule editor, next_run_at and job execution service.',
                highlights: ['recurring_rules', 'config_json', 'next_run_at', 'entity_type'],
                href: $this->tenantRoute($householdSlug, 'recurring.index'),
            ),
            'chores' => $this->module(
                key: 'chores',
                title: 'Chores',
                summary: 'Plan chores, generate occurrences, rotate assignments and track completions within the household.',
                milestone: 'Planner, rotation service and completion flow.',
                highlights: ['chores', 'chore_occurrences', 'assignment_mode', 'points'],
                href: $this->tenantRoute($householdSlug, 'chores.index'),
            ),
            'shopping' => $this->module(
                key: 'shopping',
                title: 'Shopping Lists',
                summary: 'Mobile-first shopping lists with priorities, assignments and optional conversion to expense.',
                milestone: 'List builder, item ordering and purchase workflow.',
                highlights: ['shopping_lists', 'shopping_items', 'priority', 'converted_expense_id'],
                href: $this->tenantRoute($householdSlug, 'shopping.index'),
            ),
            'pinboard' => $this->module(
                key: 'pinboard',
                title: 'Pinboard',
                summary: 'Household pinboard with notes, deadlines, comments and attachments.',
                milestone: 'Post board, comment threads and moderation controls.',
                highlights: ['pinboard_posts', 'pinboard_comments', 'attachments'],
                href: $this->tenantRoute($householdSlug, 'pinboard.index'),
            ),
            'notifications' => $this->module(
                key: 'notifications',
                title: 'Notifications',
                summary: 'In-app notification center for reminders, expenses, chores and mentions.',
                milestone: 'Read/unread feed, badge counters and service-layer dispatch.',
                highlights: ['notifications', 'read_at', 'data_json'],
                href: $this->tenantRoute($householdSlug, 'notifications.index'),
            ),
            'reports' => $this->module(
                key: 'reports',
                title: 'Reports',
                summary: 'Household dashboards and analytics for expenses, balances, chores and time trends.',
                milestone: 'Aggregate queries, period filters and member breakdowns.',
                highlights: ['materialized-like queries', 'expense aggregates', 'balances'],
                href: $this->tenantRoute($householdSlug, 'reports.index'),
            ),
            'settings' => $this->module(
                key: 'settings',
                title: 'Settings',
                summary: 'Workspace settings, locale, date format, modules and notification defaults.',
                milestone: 'Settings editor, lightweight feature flags and update service.',
                highlights: ['household_settings', 'user_preferences'],
                href: $this->tenantRoute($householdSlug, 'settings.index'),
            ),
            'attachments' => $this->module(
                key: 'attachments',
                title: 'Attachments',
                summary: 'Metadata-first storage for household-scoped files with polymorphic binding.',
                milestone: 'Secure upload, binding UI and authorized preview/download.',
                highlights: ['attachments', 'checksum_sha256', 'entity_type', 'entity_id'],
                href: $this->tenantRoute($householdSlug, 'attachments.index'),
            ),
            'audit' => $this->module(
                key: 'audit',
                title: 'Audit Log',
                summary: 'Operational trail with actor, action, JSON snapshots and tenant context.',
                milestone: 'Timeline viewer, entity/action filters and export.',
                highlights: ['audit_logs', 'before_json', 'after_json', 'actor_user_id'],
                href: $this->tenantRoute($householdSlug, 'audit.index'),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function modulePlaceholder(string $moduleKey, ?string $householdSlug = null): array
    {
        $catalog = $this->moduleCatalog($householdSlug);

        return $catalog[$moduleKey] ?? $this->module(
            key: $moduleKey,
            title: 'Module Placeholder',
            summary: 'This section is ready to be implemented in its dedicated chunk.',
            milestone: 'Use-case definition, controller, service and working UI.',
            highlights: ['routing', 'layout', 'tenant context', 'RBAC'],
            href: $this->tenantRoute($householdSlug, 'households.dashboard'),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function navigationForSidebar(?string $householdSlug, string $requestPath, string $fallbackHref, ?int $currentUserId): array
    {
        $items = [
            $this->navItem(ui_text('nav.dashboard'), 'nav-icon fas fa-tachometer-alt', $this->tenantRoute($householdSlug, 'households.dashboard'), $requestPath, ['h/.+/dashboard', 'households/(?!create$|switch$)[^/]+'], $fallbackHref),
            $this->navItem(ui_text('nav.expenses'), 'nav-icon fas fa-receipt', $this->tenantRoute($householdSlug, 'expenses.index'), $requestPath, ['h/.+/expenses(?:/.*)?', 'households/[^/]+/expenses(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.balances'), 'nav-icon fas fa-wallet', $this->tenantRoute($householdSlug, 'balances.overview'), $requestPath, ['h/.+/balances(?:/.*)?', 'households/[^/]+/balances(?:/.*)?', 'h/.+/settlements(?:/.*)?', 'households/[^/]+/settlements(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.chores'), 'nav-icon fas fa-check-square', $this->tenantRoute($householdSlug, 'chores.index'), $requestPath, ['h/.+/chores(?:/.*)?', 'households/[^/]+/chores(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.shopping'), 'nav-icon fas fa-shopping-cart', $this->tenantRoute($householdSlug, 'shopping.index'), $requestPath, ['h/.+/shopping-lists(?:/.*)?', 'households/[^/]+/shopping-lists(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.pinboard'), 'nav-icon fas fa-thumbtack', $this->tenantRoute($householdSlug, 'pinboard.index'), $requestPath, ['h/.+/pinboard(?:/.*)?', 'households/[^/]+/pinboard(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.notifications'), 'nav-icon far fa-bell', $this->tenantRoute($householdSlug, 'notifications.index'), $requestPath, ['notifications(?:/.*)?', 'h/.+/notifications(?:/.*)?', 'households/[^/]+/notifications(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.reports'), 'nav-icon fas fa-chart-pie', $this->tenantRoute($householdSlug, 'reports.index'), $requestPath, ['h/.+/reports(?:/.*)?', 'households/[^/]+/reports(?:/.*)?'], $fallbackHref, Permission::VIEW_REPORTS),
        ];

        return array_values(array_filter($items, fn (array $item): bool => $this->visible($item, $householdSlug, $currentUserId)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function navigationForUtilities(?string $householdSlug, string $requestPath, string $fallbackHref, ?int $currentUserId): array
    {
        $items = [
            $this->navItem(ui_text('nav.settlements'), 'nav-icon fas fa-exchange-alt', $this->tenantRoute($householdSlug, 'settlements.index'), $requestPath, ['h/.+/settlements(?:/.*)?', 'households/[^/]+/settlements(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.members'), 'nav-icon fas fa-users', $this->tenantRoute($householdSlug, 'memberships.index'), $requestPath, ['h/.+/members(?:/.*)?', 'households/[^/]+/members(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.invitations'), 'nav-icon far fa-envelope', $this->tenantRoute($householdSlug, 'invitations.index'), $requestPath, ['h/.+/invitations(?:/.*)?', 'households/[^/]+/invitations(?:/.*)?'], $fallbackHref, Permission::MANAGE_MEMBERS),
            $this->navItem(ui_text('nav.roles'), 'nav-icon fas fa-user-shield', $this->tenantRoute($householdSlug, 'roles.index'), $requestPath, ['h/.+/roles(?:/.*)?', 'households/[^/]+/roles(?:/.*)?'], $fallbackHref, Permission::MANAGE_ROLES),
            $this->navItem(ui_text('nav.recurring'), 'nav-icon fas fa-sync-alt', $this->tenantRoute($householdSlug, 'recurring.index'), $requestPath, ['h/.+/recurring-rules(?:/.*)?', 'households/[^/]+/recurring-rules(?:/.*)?'], $fallbackHref),
            $this->navItem(ui_text('nav.settings'), 'nav-icon fas fa-cog', $this->tenantRoute($householdSlug, 'settings.index'), $requestPath, ['h/.+/settings(?:/.*)?', 'households/[^/]+/settings(?:/.*)?'], $fallbackHref, Permission::MANAGE_SETTINGS),
        ];

        return array_values(array_filter($items, fn (array $item): bool => $this->visible($item, $householdSlug, $currentUserId)));
    }

    /**
     * @param list<string> $patterns
     * @return array<string, mixed>
     */
    private function navItem(string $label, string $icon, ?string $href, string $requestPath, array $patterns, string $fallbackHref, ?string $permission = null): array
    {
        $isActive = false;

        foreach ($patterns as $pattern) {
            if (preg_match('#^' . $pattern . '$#', $requestPath) === 1) {
                $isActive = true;
                break;
            }
        }

        return [
            'label' => $label,
            'icon' => $icon,
            'href' => $href ?? $fallbackHref,
            'isActive' => $isActive,
            'permission' => $permission,
        ];
    }

    private function visible(array $item, ?string $householdSlug, ?int $currentUserId): bool
    {
        $permission = $item['permission'] ?? null;

        if (! is_string($permission) || $permission === '') {
            return true;
        }

        if ($householdSlug === null || $currentUserId === null) {
            return false;
        }

        return ($this->householdAuthorizationService ?? service('householdAuthorization'))
            ->hasPermission($currentUserId, $householdSlug, $permission);
    }

    /**
     * @param list<string> $highlights
     * @return array<string, mixed>
     */
    private function module(
        string $key,
        string $title,
        string $summary,
        string $milestone,
        array $highlights,
        ?string $href,
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'summary' => $summary,
            'milestone' => $milestone,
            'highlights' => $highlights,
            'href' => $href,
        ];
    }

    /**
     * @param array<string, mixed>|null $activeHousehold
     * @return array<string, mixed>
     */
    private function placeholderNotifications(?array $activeHousehold): array
    {
        $householdName = $activeHousehold['household_name'] ?? (ui_locale() === 'it' ? 'Casa corrente' : 'Current household');

        return [
            'items' => [
                [
                    'id' => 0,
                    'title' => ui_locale() === 'it' ? 'Interfaccia pronta' : 'Interface ready',
                    'body' => ui_locale() === 'it'
                        ? 'Shell UI, filtri e contesto tenant sono attivi per ' . $householdName . '.'
                        : 'UI shell, filters and tenant context are active for ' . $householdName . '.',
                    'read_at' => date('Y-m-d H:i:s'),
                    'target_url' => route_url('home'),
                ],
                [
                    'id' => 0,
                    'title' => ui_locale() === 'it' ? 'Routing modulare' : 'Modular routing',
                    'body' => ui_locale() === 'it'
                        ? 'Le route placeholder sono gia allineate ai moduli definiti in architettura.'
                        : 'Placeholder routes are already aligned with the modules defined in the architecture.',
                    'read_at' => date('Y-m-d H:i:s'),
                    'target_url' => route_url('home'),
                ],
                [
                    'id' => 0,
                    'title' => ui_locale() === 'it' ? 'Tema persistente' : 'Persistent dark mode',
                    'body' => ui_locale() === 'it'
                        ? 'Tema e preferenze UI sono gestiti nel frontend senza dipendenze Node in produzione.'
                        : 'Theme and UI preferences are handled in the frontend without Node dependencies in production.',
                    'read_at' => date('Y-m-d H:i:s'),
                    'target_url' => route_url('home'),
                ],
            ],
            'unreadCount' => 0,
            'centerUrl' => route_url('home'),
            'markAllUrl' => route_url('home'),
        ];
    }

    private function tenantRoute(?string $householdSlug, string $routeName): ?string
    {
        if ($householdSlug === null || $householdSlug === '') {
            return null;
        }

        return route_url($routeName, $householdSlug);
    }
}
