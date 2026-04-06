<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

$routes->get('/', 'Web\HomeController::index', ['as' => 'home']);

$routes->group('', ['filter' => ['guest']], static function (RouteCollection $routes): void {
    $routes->get('login', 'Web\Auth\AuthController::login', ['as' => 'auth.login']);
    $routes->post('login', 'Web\Auth\AuthController::loginSubmit', ['as' => 'auth.login.submit', 'filter' => ['guest', 'rateLimitLogin']]);
    $routes->get('register', 'Web\Auth\AuthController::register', ['as' => 'auth.register']);
    $routes->post('register', 'Web\Auth\AuthController::registerSubmit', ['as' => 'auth.register.submit']);
    $routes->get('forgot-password', 'Web\Auth\AuthController::forgotPassword', ['as' => 'auth.forgot']);
    $routes->post('forgot-password', 'Web\Auth\AuthController::forgotPasswordSubmit', ['as' => 'auth.forgot.submit']);
    $routes->get('reset-password', 'Web\Auth\AuthController::forgotPassword');
    $routes->get('reset-password/(:segment)', 'Web\Auth\AuthController::resetPassword/$1', ['as' => 'auth.reset']);
    $routes->post('reset-password/(:segment)', 'Web\Auth\AuthController::resetPasswordSubmit/$1', ['as' => 'auth.reset.submit']);
});

$routes->get('email/verify/(:segment)', 'Web\Auth\AuthController::verifyEmailToken/$1', ['as' => 'auth.verify.token']);
$routes->get('invitations/accept/(:segment)', 'Web\Households\InvitationController::accept/$1', ['as' => 'invitations.accept']);
$routes->post('invitations/accept/(:segment)', 'Web\Households\InvitationController::acceptSubmit/$1', ['as' => 'invitations.accept.submit']);

$routes->group('', ['filter' => ['auth']], static function (RouteCollection $routes): void {
    $routes->post('logout', 'Web\Auth\AuthController::logout', ['as' => 'auth.logout']);
    $routes->get('email/verify', 'Web\Auth\AuthController::verifyNotice', ['as' => 'email.verify.notice']);
    $routes->post('email/verify/resend', 'Web\Auth\AuthController::resendVerification', ['as' => 'email.verify.resend']);
    $routes->get('notifications', 'Web\Notifications\NotificationController::accountIndex', ['as' => 'notifications.global']);
    $routes->get('notifications/poll', 'Web\Notifications\NotificationController::poll', ['as' => 'notifications.poll.global']);
    $routes->post('notifications/read-all', 'Web\Notifications\NotificationController::readAll', ['as' => 'notifications.read_all.global']);
    $routes->post('notifications/(:num)/read', 'Web\Notifications\NotificationController::read/$1', ['as' => 'notifications.read']);

    $routes->get('profile', 'Web\Auth\ProfileController::edit', ['as' => 'profile.edit']);
    $routes->post('profile', 'Web\Auth\ProfileController::update', ['as' => 'profile.update']);

    $routes->get('households', 'Web\Households\HouseholdController::index', ['as' => 'households.index']);
    $routes->get('households/create', 'Web\Households\HouseholdController::create', ['as' => 'households.create']);
    $routes->post('households/create', 'Web\Households\HouseholdController::store', ['as' => 'households.store']);
    $routes->post('households', 'Web\Households\HouseholdController::store', ['as' => 'households.store.legacy']);
    $routes->get('households/switch', 'Web\Households\HouseholdController::switcher', ['as' => 'households.switcher']);
    $routes->post('households/switch/(:segment)', 'Web\Households\HouseholdController::switch/$1', ['as' => 'households.switch']);

    $routes->get('households/(:segment)', 'Web\Households\HouseholdController::dashboard/$1', ['as' => 'households.dashboard', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/members', 'Web\Households\MembershipController::index/$1', ['as' => 'memberships.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/members/(:num)', 'Web\Households\MembershipController::show/$1/$2', ['as' => 'memberships.show', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/members/(:num)/roles', 'Web\Roles\MembershipRoleController::edit/$1/$2', ['as' => 'membership.roles.edit', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->post('households/(:segment)/members/(:num)/roles', 'Web\Roles\MembershipRoleController::update/$1/$2', ['as' => 'membership.roles.update', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->get('households/(:segment)/invitations', 'Web\Households\InvitationController::index/$1', ['as' => 'invitations.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/balances', 'Web\Balances\BalanceController::overview/$1', ['as' => 'balances.overview', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/balances/personal', 'Web\Balances\BalanceController::personal/$1', ['as' => 'balances.personal', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/balances/pairwise', 'Web\Balances\BalanceController::pairwise/$1', ['as' => 'balances.pairwise', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/chores', 'Web\Chores\ChoreController::index/$1', ['as' => 'chores.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/chores/templates', 'Web\Chores\ChoreController::templates/$1', ['as' => 'chores.templates', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/chores/create', 'Web\Chores\ChoreController::create/$1', ['as' => 'chores.create', 'filter' => ['currentHousehold', 'permission:manage_chores']]);
    $routes->post('households/(:segment)/chores', 'Web\Chores\ChoreController::store/$1', ['as' => 'chores.store', 'filter' => ['currentHousehold', 'permission:manage_chores']]);
    $routes->get('households/(:segment)/chores/(:num)/edit', 'Web\Chores\ChoreController::edit/$1/$2', ['as' => 'chores.edit', 'filter' => ['currentHousehold', 'permission:manage_chores']]);
    $routes->post('households/(:segment)/chores/(:num)/update', 'Web\Chores\ChoreController::update/$1/$2', ['as' => 'chores.update', 'filter' => ['currentHousehold', 'permission:manage_chores']]);
    $routes->post('households/(:segment)/chores/(:num)/toggle', 'Web\Chores\ChoreController::toggle/$1/$2', ['as' => 'chores.toggle', 'filter' => ['currentHousehold', 'permission:manage_chores']]);
    $routes->get('households/(:segment)/chores/occurrences', 'Web\Chores\ChoreOccurrenceController::index/$1', ['as' => 'chores.occurrences', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/chores/(:num)/occurrences', 'Web\Chores\ChoreOccurrenceController::createForTemplate/$1/$2', ['as' => 'chores.occurrence.create', 'filter' => ['currentHousehold', 'permission:manage_chores']]);
    $routes->get('households/(:segment)/chores/my', 'Web\Chores\ChoreOccurrenceController::my/$1', ['as' => 'chores.my', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/chores/calendar', 'Web\Chores\ChoreOccurrenceController::calendar/$1', ['as' => 'chores.calendar', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/chores/fairness', 'Web\Chores\ChoreOccurrenceController::fairness/$1', ['as' => 'chores.fairness', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/chores/occurrences/(:num)/complete', 'Web\Chores\ChoreOccurrenceController::complete/$1/$2', ['as' => 'chores.complete', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/chores/occurrences/(:num)/skip', 'Web\Chores\ChoreOccurrenceController::skip/$1/$2', ['as' => 'chores.skip', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/expenses', 'Web\Expenses\ExpenseController::index/$1', ['as' => 'expenses.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/expenses/create', 'Web\Expenses\ExpenseController::create/$1', ['as' => 'expenses.create', 'filter' => ['currentHousehold', 'permission:create_expense']]);
    $routes->post('households/(:segment)/expenses', 'Web\Expenses\ExpenseController::store/$1', ['as' => 'expenses.store', 'filter' => ['currentHousehold', 'permission:create_expense']]);
    $routes->post('households/(:segment)/expenses/groups', 'Web\Expenses\ExpenseController::createGroup/$1', ['as' => 'expenses.groups.create', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/expenses/groups/(:num)/update', 'Web\Expenses\ExpenseController::updateGroup/$1/$2', ['as' => 'expenses.groups.update', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/expenses/groups/(:num)/delete', 'Web\Expenses\ExpenseController::deleteGroup/$1/$2', ['as' => 'expenses.groups.delete', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/expenses/(:num)', 'Web\Expenses\ExpenseController::show/$1/$2', ['as' => 'expenses.show', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/expenses/(:num)/edit', 'Web\Expenses\ExpenseController::edit/$1/$2', ['as' => 'expenses.edit', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/expenses/(:num)/update', 'Web\Expenses\ExpenseController::update/$1/$2', ['as' => 'expenses.update', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/expenses/(:num)/delete', 'Web\Expenses\ExpenseController::delete/$1/$2', ['as' => 'expenses.delete', 'filter' => ['currentHousehold', 'permission:delete_expense']]);
    $routes->get('households/(:segment)/expenses/(:num)/receipt', 'Web\Expenses\ExpenseController::receipt/$1/$2', ['as' => 'expenses.receipt', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/settlements', 'Web\Balances\SettlementController::index/$1', ['as' => 'settlements.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/settlements/create', 'Web\Balances\SettlementController::create/$1', ['as' => 'settlements.create', 'filter' => ['currentHousehold', 'permission:add_settlement']]);
    $routes->post('households/(:segment)/settlements', 'Web\Balances\SettlementController::store/$1', ['as' => 'settlements.store', 'filter' => ['currentHousehold', 'permission:add_settlement']]);
    $routes->get('households/(:segment)/settlements/(:num)/attachment', 'Web\Balances\SettlementController::attachment/$1/$2', ['as' => 'settlements.attachment', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/recurring-rules', 'Web\Recurring\RecurringExpenseController::index/$1', ['as' => 'recurring.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/recurring-rules/create', 'Web\Recurring\RecurringExpenseController::create/$1', ['as' => 'recurring.create', 'filter' => ['currentHousehold', 'permission:create_expense']]);
    $routes->post('households/(:segment)/recurring-rules', 'Web\Recurring\RecurringExpenseController::store/$1', ['as' => 'recurring.store', 'filter' => ['currentHousehold', 'permission:create_expense']]);
    $routes->get('households/(:segment)/recurring-rules/(:num)/edit', 'Web\Recurring\RecurringExpenseController::edit/$1/$2', ['as' => 'recurring.edit', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/recurring-rules/(:num)/update', 'Web\Recurring\RecurringExpenseController::update/$1/$2', ['as' => 'recurring.update', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/recurring-rules/(:num)/disable', 'Web\Recurring\RecurringExpenseController::disable/$1/$2', ['as' => 'recurring.disable', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/roles', 'Web\Roles\RoleController::index/$1', ['as' => 'roles.index', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->get('households/(:segment)/roles/create', 'Web\Roles\RoleController::create/$1', ['as' => 'roles.create', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->post('households/(:segment)/roles', 'Web\Roles\RoleController::store/$1', ['as' => 'roles.store', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->get('households/(:segment)/roles/(:num)', 'Web\Roles\RoleController::show/$1/$2', ['as' => 'roles.show', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->get('households/(:segment)/roles/(:num)/edit', 'Web\Roles\RoleController::edit/$1/$2', ['as' => 'roles.edit', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->post('households/(:segment)/roles/(:num)/update', 'Web\Roles\RoleController::update/$1/$2', ['as' => 'roles.update', 'filter' => ['currentHousehold', 'permission:manage_roles']]);
    $routes->get('households/(:segment)/shopping-lists', 'Web\Shopping\ShoppingListController::index/$1', ['as' => 'shopping.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/shopping-lists/create', 'Web\Shopping\ShoppingListController::create/$1', ['as' => 'shopping.create', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-lists', 'Web\Shopping\ShoppingListController::store/$1', ['as' => 'shopping.store', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->get('households/(:segment)/shopping-lists/(:num)', 'Web\Shopping\ShoppingListController::show/$1/$2', ['as' => 'shopping.show', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/shopping-lists/(:num)/edit', 'Web\Shopping\ShoppingListController::edit/$1/$2', ['as' => 'shopping.edit', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-lists/(:num)/update', 'Web\Shopping\ShoppingListController::update/$1/$2', ['as' => 'shopping.update', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-lists/(:num)/delete', 'Web\Shopping\ShoppingListController::delete/$1/$2', ['as' => 'shopping.delete', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-lists/(:num)/items', 'Web\Shopping\ShoppingItemController::store/$1/$2', ['as' => 'shopping.items.store', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-items/(:num)/update', 'Web\Shopping\ShoppingItemController::update/$1/$2', ['as' => 'shopping.items.update', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-items/(:num)/delete', 'Web\Shopping\ShoppingItemController::delete/$1/$2', ['as' => 'shopping.items.delete', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-items/(:num)/toggle', 'Web\Shopping\ShoppingItemController::togglePurchased/$1/$2', ['as' => 'shopping.items.toggle', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-lists/(:num)/bulk', 'Web\Shopping\ShoppingItemController::bulkPurchased/$1/$2', ['as' => 'shopping.items.bulk', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->post('households/(:segment)/shopping-lists/(:num)/convert-expense', 'Web\Shopping\ShoppingItemController::convertToExpense/$1/$2', ['as' => 'shopping.convert', 'filter' => ['currentHousehold', 'permission:manage_shopping']]);
    $routes->get('households/(:segment)/pinboard', 'Web\Pinboard\PinboardController::index/$1', ['as' => 'pinboard.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/pinboard/create', 'Web\Pinboard\PinboardController::create/$1', ['as' => 'pinboard.create', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->post('households/(:segment)/pinboard', 'Web\Pinboard\PinboardController::store/$1', ['as' => 'pinboard.store', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->get('households/(:segment)/pinboard/(:num)', 'Web\Pinboard\PinboardController::show/$1/$2', ['as' => 'pinboard.show', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/pinboard/(:num)/edit', 'Web\Pinboard\PinboardController::edit/$1/$2', ['as' => 'pinboard.edit', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->post('households/(:segment)/pinboard/(:num)/update', 'Web\Pinboard\PinboardController::update/$1/$2', ['as' => 'pinboard.update', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->post('households/(:segment)/pinboard/(:num)/pin', 'Web\Pinboard\PinboardController::togglePin/$1/$2', ['as' => 'pinboard.pin', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->post('households/(:segment)/pinboard/(:num)/delete', 'Web\Pinboard\PinboardController::delete/$1/$2', ['as' => 'pinboard.delete', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->post('households/(:segment)/pinboard/(:num)/comments', 'Web\Pinboard\PinboardCommentController::store/$1/$2', ['as' => 'pinboard.comments.store', 'filter' => ['currentHousehold', 'permission:manage_pinboard']]);
    $routes->get('households/(:segment)/pinboard/(:num)/attachments/(:num)', 'Web\Pinboard\PinboardController::attachment/$1/$2/$3', ['as' => 'pinboard.attachment', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/settings', 'Web\Households\HouseholdController::settings/$1', ['as' => 'settings.index', 'filter' => ['currentHousehold', 'permission:manage_settings']]);
    $routes->post('households/(:segment)/settings', 'Web\Households\HouseholdController::updateSettings/$1', ['as' => 'settings.update', 'filter' => ['currentHousehold', 'permission:manage_settings']]);
    $routes->post('households/(:segment)/settings/expense-groups', 'Web\Households\HouseholdController::createExpenseGroup/$1', ['as' => 'settings.expense_groups.create', 'filter' => ['currentHousehold', 'permission:manage_settings']]);
    $routes->post('households/(:segment)/settings/expense-groups/(:num)/update', 'Web\Households\HouseholdController::updateExpenseGroup/$1/$2', ['as' => 'settings.expense_groups.update', 'filter' => ['currentHousehold', 'permission:manage_settings']]);
    $routes->post('households/(:segment)/settings/expense-groups/(:num)/delete', 'Web\Households\HouseholdController::deleteExpenseGroup/$1/$2', ['as' => 'settings.expense_groups.delete', 'filter' => ['currentHousehold', 'permission:manage_settings']]);
    $routes->post('households/(:segment)/invitations', 'Web\Households\InvitationController::create/$1', ['as' => 'invitations.create', 'filter' => ['currentHousehold', 'permission:manage_members']]);
    $routes->post('households/(:segment)/invitations/(:num)/revoke', 'Web\Households\InvitationController::revoke/$1/$2', ['as' => 'invitations.revoke', 'filter' => ['currentHousehold', 'permission:manage_members']]);

    $routes->get('households/(:segment)/notifications', 'Web\Notifications\NotificationController::index/$1', ['as' => 'notifications.index', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/notifications/poll', 'Web\Notifications\NotificationController::poll/$1', ['as' => 'notifications.poll', 'filter' => ['currentHousehold']]);
    $routes->post('households/(:segment)/notifications/read-all', 'Web\Notifications\NotificationController::readAll/$1', ['as' => 'notifications.read_all', 'filter' => ['currentHousehold']]);
    $routes->get('households/(:segment)/reports', 'Web\Reports\ReportController::index/$1', ['as' => 'reports.index', 'filter' => ['currentHousehold', 'permission:view_reports']]);
    $routes->get('households/(:segment)/reports/expenses', 'Web\Reports\ReportController::expenses/$1', ['as' => 'reports.expenses', 'filter' => ['currentHousehold', 'permission:view_reports']]);
    $routes->get('households/(:segment)/reports/chores', 'Web\Reports\ReportController::chores/$1', ['as' => 'reports.chores', 'filter' => ['currentHousehold', 'permission:view_reports']]);

    $tenantPlaceholders = [
        ['path' => 'attachments', 'name' => 'attachments.index', 'module' => 'attachments'],
        ['path' => 'audit-logs', 'name' => 'audit.index', 'module' => 'audit'],
    ];

    foreach ($tenantPlaceholders as $tenantPlaceholder) {
        $routes->get(
            'households/(:segment)/' . $tenantPlaceholder['path'],
            'Web\App\ModulePlaceholderController::show/$1/' . $tenantPlaceholder['module'],
            [
                'as' => $tenantPlaceholder['name'],
                'filter' => ['currentHousehold'],
            ],
        );
    }
});

$routes->group('auth', static function (RouteCollection $routes): void {
    $routes->get('login', 'Web\Auth\AuthController::login');
    $routes->post('login', 'Web\Auth\AuthController::loginSubmit', ['filter' => ['guest', 'rateLimitLogin']]);
    $routes->get('register', 'Web\Auth\AuthController::register');
    $routes->post('register', 'Web\Auth\AuthController::registerSubmit');
    $routes->get('forgot-password', 'Web\Auth\AuthController::forgotPassword');
    $routes->post('forgot-password', 'Web\Auth\AuthController::forgotPasswordSubmit');
    $routes->get('reset-password/(:segment)', 'Web\Auth\AuthController::resetPassword/$1');
    $routes->post('reset-password/(:segment)', 'Web\Auth\AuthController::resetPasswordSubmit/$1');
    $routes->get('verify-email/(:segment)', 'Web\Auth\AuthController::verifyEmailToken/$1');
    $routes->post('logout', 'Web\Auth\AuthController::logout', ['filter' => ['auth']]);
});

$routes->get('app', 'Web\WorkspaceController::index', ['as' => 'app.index', 'filter' => ['auth']]);
$routes->get('app/households', 'Web\WorkspaceController::households', ['as' => 'app.households', 'filter' => ['auth']]);
$routes->post('app/households/switch/(:segment)', 'Web\WorkspaceController::switchHousehold/$1', ['filter' => ['auth']]);
$routes->get('h/(:segment)/dashboard', 'Web\WorkspaceController::dashboard/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/balances', 'Web\Balances\BalanceController::overview/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/balances/personal', 'Web\Balances\BalanceController::personal/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/balances/pairwise', 'Web\Balances\BalanceController::pairwise/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/chores', 'Web\Chores\ChoreController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/expenses', 'Web\Expenses\ExpenseController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/settlements', 'Web\Balances\SettlementController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/recurring-rules', 'Web\Recurring\RecurringExpenseController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/roles', 'Web\Roles\RoleController::index/$1', ['filter' => ['auth', 'currentHousehold', 'permission:manage_roles']]);
$routes->get('h/(:segment)/shopping-lists', 'Web\Shopping\ShoppingListController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/pinboard', 'Web\Pinboard\PinboardController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/notifications', 'Web\Notifications\NotificationController::index/$1', ['filter' => ['auth', 'currentHousehold']]);
$routes->get('h/(:segment)/reports', 'Web\Reports\ReportController::index/$1', ['filter' => ['auth', 'currentHousehold', 'permission:view_reports']]);
$routes->get('h/(:segment)/reports/expenses', 'Web\Reports\ReportController::expenses/$1', ['filter' => ['auth', 'currentHousehold', 'permission:view_reports']]);
$routes->get('h/(:segment)/reports/chores', 'Web\Reports\ReportController::chores/$1', ['filter' => ['auth', 'currentHousehold', 'permission:view_reports']]);

$legacyPlaceholders = [
    'attachments' => 'attachments',
    'audit-logs' => 'audit',
];

foreach ($legacyPlaceholders as $path => $module) {
    $routes->get('h/(:segment)/' . $path, 'Web\App\ModulePlaceholderController::show/$1/' . $module, ['filter' => ['auth', 'currentHousehold']]);
}
