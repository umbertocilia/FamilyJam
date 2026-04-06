<?php

namespace Config;

use App\Services\Audit\AuditLogService;
use App\Services\Attachments\AttachmentStorageService;
use App\Services\Balances\BalanceService;
use App\Services\Balances\DebtSimplificationService;
use App\Services\Balances\SettlementService;
use App\Services\Chores\ChoreFairnessService;
use App\Services\Chores\ChoreOccurrenceService;
use App\Services\Chores\ChoreRecurringExecutionService;
use App\Services\Chores\ChoreReminderService;
use App\Services\Chores\ChoreRotationService;
use App\Services\Chores\ChoreService;
use App\Services\Notifications\NotificationService;
use App\Services\Pinboard\PinboardCommentService;
use App\Services\Pinboard\PinboardService;
use App\Services\Reports\DashboardService;
use App\Services\Reports\ReportService;
use App\Services\Recurring\RecurringExpenseExecutionService;
use App\Services\Recurring\RecurringExpenseService;
use App\Services\Recurring\RecurringScheduleService;
use App\Services\Shopping\ShoppingConversionService;
use App\Services\Shopping\ShoppingItemService;
use App\Services\Shopping\ShoppingListService;
use App\Services\Authorization\HouseholdAuthorizationService;
use App\Services\Authorization\RoleManagementService;
use App\Services\Auth\AuthTokenService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\LoginThrottleService;
use App\Services\Auth\OutboundEmailService;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\RegistrationService;
use App\Services\Auth\SessionAuthService;
use App\Services\Auth\UserProfileService;
use App\Services\Expenses\ExpenseService;
use App\Services\Expenses\ExpenseValidationService;
use App\Services\Expenses\SplitCalculationService;
use App\Services\Households\HouseholdContextService;
use App\Services\Households\HouseholdManagementService;
use App\Services\Households\HouseholdProvisioningService;
use App\Services\Households\InvitationService;
use App\Services\Households\MembershipService;
use App\Services\Media\AvatarImageService;
use App\Services\UI\AppShellService;
use App\Services\UI\DashboardPreviewService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function attachmentStorage(bool $getShared = true): AttachmentStorageService
    {
        if ($getShared) {
            /** @var AttachmentStorageService $service */
            $service = static::getSharedInstance('attachmentStorage');

            return $service;
        }

        return new AttachmentStorageService();
    }

    public static function avatarImages(bool $getShared = true): AvatarImageService
    {
        if ($getShared) {
            /** @var AvatarImageService $service */
            $service = static::getSharedInstance('avatarImages');

            return $service;
        }

        return new AvatarImageService();
    }

    public static function authToken(bool $getShared = true): AuthTokenService
    {
        if ($getShared) {
            /** @var AuthTokenService $service */
            $service = static::getSharedInstance('authToken');

            return $service;
        }

        return new AuthTokenService();
    }

    public static function appShell(bool $getShared = true): AppShellService
    {
        if ($getShared) {
            /** @var AppShellService $service */
            $service = static::getSharedInstance('appShell');

            return $service;
        }

        return new AppShellService();
    }

    public static function auditLogger(bool $getShared = true): AuditLogService
    {
        if ($getShared) {
            /** @var AuditLogService $service */
            $service = static::getSharedInstance('auditLogger');

            return $service;
        }

        return new AuditLogService();
    }

    public static function emailVerification(bool $getShared = true): EmailVerificationService
    {
        if ($getShared) {
            /** @var EmailVerificationService $service */
            $service = static::getSharedInstance('emailVerification');

            return $service;
        }

        return new EmailVerificationService();
    }

    public static function expenseService(bool $getShared = true): ExpenseService
    {
        if ($getShared) {
            /** @var ExpenseService $service */
            $service = static::getSharedInstance('expenseService');

            return $service;
        }

        return new ExpenseService();
    }

    public static function expenseValidation(bool $getShared = true): ExpenseValidationService
    {
        if ($getShared) {
            /** @var ExpenseValidationService $service */
            $service = static::getSharedInstance('expenseValidation');

            return $service;
        }

        return new ExpenseValidationService();
    }

    public static function dashboardPreview(bool $getShared = true): DashboardPreviewService
    {
        if ($getShared) {
            /** @var DashboardPreviewService $service */
            $service = static::getSharedInstance('dashboardPreview');

            return $service;
        }

        return new DashboardPreviewService();
    }

    public static function householdDashboard(bool $getShared = true): DashboardService
    {
        if ($getShared) {
            /** @var DashboardService $service */
            $service = static::getSharedInstance('householdDashboard');

            return $service;
        }

        return new DashboardService();
    }

    public static function householdAuthorization(bool $getShared = true): HouseholdAuthorizationService
    {
        if ($getShared) {
            /** @var HouseholdAuthorizationService $service */
            $service = static::getSharedInstance('householdAuthorization');

            return $service;
        }

        return new HouseholdAuthorizationService();
    }

    public static function roleManager(bool $getShared = true): RoleManagementService
    {
        if ($getShared) {
            /** @var RoleManagementService $service */
            $service = static::getSharedInstance('roleManager');

            return $service;
        }

        return new RoleManagementService();
    }

    public static function householdManager(bool $getShared = true): HouseholdManagementService
    {
        if ($getShared) {
            /** @var HouseholdManagementService $service */
            $service = static::getSharedInstance('householdManager');

            return $service;
        }

        return new HouseholdManagementService();
    }

    public static function householdContext(bool $getShared = true): HouseholdContextService
    {
        if ($getShared) {
            /** @var HouseholdContextService $service */
            $service = static::getSharedInstance('householdContext');

            return $service;
        }

        return new HouseholdContextService();
    }

    public static function householdInvitation(bool $getShared = true): InvitationService
    {
        if ($getShared) {
            /** @var InvitationService $service */
            $service = static::getSharedInstance('householdInvitation');

            return $service;
        }

        return new InvitationService();
    }

    public static function householdMemberships(bool $getShared = true): MembershipService
    {
        if ($getShared) {
            /** @var MembershipService $service */
            $service = static::getSharedInstance('householdMemberships');

            return $service;
        }

        return new MembershipService();
    }

    public static function householdProvisioning(bool $getShared = true): HouseholdProvisioningService
    {
        if ($getShared) {
            /** @var HouseholdProvisioningService $service */
            $service = static::getSharedInstance('householdProvisioning');

            return $service;
        }

        return new HouseholdProvisioningService();
    }

    public static function reportService(bool $getShared = true): ReportService
    {
        if ($getShared) {
            /** @var ReportService $service */
            $service = static::getSharedInstance('reportService');

            return $service;
        }

        return new ReportService();
    }

    public static function loginThrottle(bool $getShared = true): LoginThrottleService
    {
        if ($getShared) {
            /** @var LoginThrottleService $service */
            $service = static::getSharedInstance('loginThrottle');

            return $service;
        }

        return new LoginThrottleService();
    }

    public static function splitCalculator(bool $getShared = true): SplitCalculationService
    {
        if ($getShared) {
            /** @var SplitCalculationService $service */
            $service = static::getSharedInstance('splitCalculator');

            return $service;
        }

        return new SplitCalculationService();
    }

    public static function outboundEmail(bool $getShared = true): OutboundEmailService
    {
        if ($getShared) {
            /** @var OutboundEmailService $service */
            $service = static::getSharedInstance('outboundEmail');

            return $service;
        }

        return new OutboundEmailService();
    }

    public static function passwordReset(bool $getShared = true): PasswordResetService
    {
        if ($getShared) {
            /** @var PasswordResetService $service */
            $service = static::getSharedInstance('passwordReset');

            return $service;
        }

        return new PasswordResetService();
    }

    public static function registration(bool $getShared = true): RegistrationService
    {
        if ($getShared) {
            /** @var RegistrationService $service */
            $service = static::getSharedInstance('registration');

            return $service;
        }

        return new RegistrationService();
    }

    public static function sessionAuth(bool $getShared = true): SessionAuthService
    {
        if ($getShared) {
            /** @var SessionAuthService $service */
            $service = static::getSharedInstance('sessionAuth');

            return $service;
        }

        return new SessionAuthService();
    }

    public static function userProfile(bool $getShared = true): UserProfileService
    {
        if ($getShared) {
            /** @var UserProfileService $service */
            $service = static::getSharedInstance('userProfile');

            return $service;
        }

        return new UserProfileService();
    }

    public static function balanceService(bool $getShared = true): BalanceService
    {
        if ($getShared) {
            /** @var BalanceService $service */
            $service = static::getSharedInstance('balanceService');

            return $service;
        }

        return new BalanceService();
    }

    public static function choreService(bool $getShared = true): ChoreService
    {
        if ($getShared) {
            /** @var ChoreService $service */
            $service = static::getSharedInstance('choreService');

            return $service;
        }

        return new ChoreService();
    }

    public static function choreOccurrenceService(bool $getShared = true): ChoreOccurrenceService
    {
        if ($getShared) {
            /** @var ChoreOccurrenceService $service */
            $service = static::getSharedInstance('choreOccurrenceService');

            return $service;
        }

        return new ChoreOccurrenceService();
    }

    public static function choreFairnessService(bool $getShared = true): ChoreFairnessService
    {
        if ($getShared) {
            /** @var ChoreFairnessService $service */
            $service = static::getSharedInstance('choreFairnessService');

            return $service;
        }

        return new ChoreFairnessService();
    }

    public static function choreRotation(bool $getShared = true): ChoreRotationService
    {
        if ($getShared) {
            /** @var ChoreRotationService $service */
            $service = static::getSharedInstance('choreRotation');

            return $service;
        }

        return new ChoreRotationService();
    }

    public static function choreRecurringExecutor(bool $getShared = true): ChoreRecurringExecutionService
    {
        if ($getShared) {
            /** @var ChoreRecurringExecutionService $service */
            $service = static::getSharedInstance('choreRecurringExecutor');

            return $service;
        }

        return new ChoreRecurringExecutionService();
    }

    public static function choreReminderService(bool $getShared = true): ChoreReminderService
    {
        if ($getShared) {
            /** @var ChoreReminderService $service */
            $service = static::getSharedInstance('choreReminderService');

            return $service;
        }

        return new ChoreReminderService();
    }

    public static function notificationService(bool $getShared = true): NotificationService
    {
        if ($getShared) {
            /** @var NotificationService $service */
            $service = static::getSharedInstance('notificationService');

            return $service;
        }

        return new NotificationService();
    }

    public static function pinboardService(bool $getShared = true): PinboardService
    {
        if ($getShared) {
            /** @var PinboardService $service */
            $service = static::getSharedInstance('pinboardService');

            return $service;
        }

        return new PinboardService();
    }

    public static function pinboardCommentService(bool $getShared = true): PinboardCommentService
    {
        if ($getShared) {
            /** @var PinboardCommentService $service */
            $service = static::getSharedInstance('pinboardCommentService');

            return $service;
        }

        return new PinboardCommentService();
    }

    public static function shoppingListService(bool $getShared = true): ShoppingListService
    {
        if ($getShared) {
            /** @var ShoppingListService $service */
            $service = static::getSharedInstance('shoppingListService');

            return $service;
        }

        return new ShoppingListService();
    }

    public static function shoppingItemService(bool $getShared = true): ShoppingItemService
    {
        if ($getShared) {
            /** @var ShoppingItemService $service */
            $service = static::getSharedInstance('shoppingItemService');

            return $service;
        }

        return new ShoppingItemService();
    }

    public static function shoppingConversionService(bool $getShared = true): ShoppingConversionService
    {
        if ($getShared) {
            /** @var ShoppingConversionService $service */
            $service = static::getSharedInstance('shoppingConversionService');

            return $service;
        }

        return new ShoppingConversionService();
    }

    public static function debtSimplifier(bool $getShared = true): DebtSimplificationService
    {
        if ($getShared) {
            /** @var DebtSimplificationService $service */
            $service = static::getSharedInstance('debtSimplifier');

            return $service;
        }

        return new DebtSimplificationService();
    }

    public static function settlementService(bool $getShared = true): SettlementService
    {
        if ($getShared) {
            /** @var SettlementService $service */
            $service = static::getSharedInstance('settlementService');

            return $service;
        }

        return new SettlementService();
    }

    public static function recurringExpenseService(bool $getShared = true): RecurringExpenseService
    {
        if ($getShared) {
            /** @var RecurringExpenseService $service */
            $service = static::getSharedInstance('recurringExpenseService');

            return $service;
        }

        return new RecurringExpenseService();
    }

    public static function recurringExpenseExecutor(bool $getShared = true): RecurringExpenseExecutionService
    {
        if ($getShared) {
            /** @var RecurringExpenseExecutionService $service */
            $service = static::getSharedInstance('recurringExpenseExecutor');

            return $service;
        }

        return new RecurringExpenseExecutionService();
    }

    public static function recurringSchedule(bool $getShared = true): RecurringScheduleService
    {
        if ($getShared) {
            /** @var RecurringScheduleService $service */
            $service = static::getSharedInstance('recurringSchedule');

            return $service;
        }

        return new RecurringScheduleService();
    }
}
