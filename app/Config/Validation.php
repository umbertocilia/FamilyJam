<?php

namespace Config;

use App\Validation\AuthRules;
use App\Validation\AppRules;
use App\Validation\ChoreRules;
use App\Validation\ExpenseRules;
use App\Validation\PinboardRules;
use App\Validation\RecurringRules;
use App\Validation\ShoppingRules;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        AppRules::class,
        AuthRules::class,
        ChoreRules::class,
        ExpenseRules::class,
        PinboardRules::class,
        RecurringRules::class,
        ShoppingRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * @var array<string, list<string>|string>
     */
    public array $householdCreate = [
        'name' => 'required|min_length[3]|max_length[120]',
        'description' => 'permit_empty|max_length[1000]',
        'base_currency' => 'required|valid_currency_code',
        'timezone' => 'required|valid_timezone|max_length[64]',
        'locale' => 'required|in_list[it,en]',
        'simplify_debts' => 'permit_empty|in_list[0,1]',
        'chore_scoring_enabled' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $memberInvitation = [
        'email' => 'required|valid_email|max_length[190]',
        'role_code' => 'required|valid_role_code',
        'message' => 'permit_empty|max_length[500]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $customRoleCreate = [
        'name' => 'required|min_length[3]|max_length[120]',
        'code' => 'required|valid_role_code|max_length[64]',
        'description' => 'permit_empty|max_length[500]',
        'permission_codes' => 'permit_empty',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $customRoleUpdate = [
        'name' => 'required|min_length[3]|max_length[120]',
        'code' => 'required|valid_role_code|max_length[64]',
        'description' => 'permit_empty|max_length[500]',
        'permission_codes' => 'permit_empty',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $membershipRoleAssignment = [
        'role_ids' => 'permit_empty',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $authLogin = [
        'email' => 'required|valid_email|max_length[190]',
        'password' => 'required|min_length[10]|max_length[255]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $authRegister = [
        'email' => 'required|valid_email|max_length[190]',
        'password' => 'required|strong_password|max_length[255]',
        'password_confirmation' => 'required|matches[password]',
        'display_name' => 'permit_empty|min_length[2]|max_length[120]',
        'first_name' => 'permit_empty|max_length[80]',
        'last_name' => 'permit_empty|max_length[80]',
        'locale' => 'required|in_list[it,en]',
        'theme' => 'required|valid_theme',
        'timezone' => 'required|valid_timezone|max_length[64]',
        'email_notifications' => 'permit_empty|in_list[0,1]',
        'invite_token' => 'permit_empty|max_length[128]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $forgotPasswordRequest = [
        'email' => 'required|valid_email|max_length[190]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $authPasswordReset = [
        'password' => 'required|strong_password|max_length[255]',
        'password_confirmation' => 'required|matches[password]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $userProfileUpdate = [
        'display_name' => 'required|min_length[2]|max_length[120]',
        'first_name' => 'permit_empty|max_length[80]',
        'last_name' => 'permit_empty|max_length[80]',
        'avatar_path' => 'permit_empty|max_length[255]',
        'locale' => 'required|in_list[it,en]',
        'theme' => 'required|valid_theme',
        'timezone' => 'required|valid_timezone|max_length[64]',
        'email_notifications' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $accountDelete = [
        'current_password' => 'required|max_length[255]',
        'confirmation_phrase' => 'required|max_length[32]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $householdUpdate = [
        'name' => 'required|min_length[3]|max_length[120]',
        'description' => 'permit_empty|max_length[1000]',
        'avatar_path' => 'permit_empty|max_length[255]',
        'base_currency' => 'required|valid_currency_code',
        'timezone' => 'required|valid_timezone|max_length[64]',
        'locale' => 'permit_empty|in_list[it,en]',
        'simplify_debts' => 'permit_empty|in_list[0,1]',
        'chore_scoring_enabled' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $expenseCreate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'description' => 'permit_empty|max_length[4000]',
        'expense_date' => 'required|valid_date[Y-m-d]',
        'currency' => 'required|valid_currency_code',
        'total_amount' => 'required|positive_money',
        'category_id' => 'permit_empty|is_natural_no_zero',
        'expense_group_id' => 'permit_empty|is_natural_no_zero',
        'split_method' => 'required|valid_split_method',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $expenseUpdate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'description' => 'permit_empty|max_length[4000]',
        'expense_date' => 'required|valid_date[Y-m-d]',
        'currency' => 'required|valid_currency_code',
        'total_amount' => 'required|positive_money',
        'category_id' => 'permit_empty|is_natural_no_zero',
        'expense_group_id' => 'permit_empty|is_natural_no_zero',
        'split_method' => 'required|valid_split_method',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $expenseFilters = [
        'category_id' => 'permit_empty|is_natural_no_zero',
        'expense_group_id' => 'permit_empty|is_natural_no_zero',
        'month' => 'permit_empty|valid_month_filter',
        'member_id' => 'permit_empty|is_natural_no_zero',
        'status' => 'permit_empty|in_list[active,edited,deleted,disputed]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $settlementCreate = [
        'from_user_id' => 'required|is_natural_no_zero',
        'to_user_id' => 'required|is_natural_no_zero',
        'expense_group_id' => 'permit_empty|is_natural_no_zero',
        'settlement_date' => 'required|valid_date[Y-m-d]',
        'currency' => 'required|valid_currency_code',
        'amount' => 'required|positive_money',
        'payment_method' => 'permit_empty|max_length[32]',
        'note' => 'permit_empty|max_length[4000]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $reportExpenseFilters = [
        'months' => 'permit_empty|in_list[1,3,6,12]',
        'member_id' => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $reportChoreFilters = [
        'days' => 'permit_empty|in_list[7,30,90]',
        'assigned_user_id' => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $recurringExpenseCreate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'description' => 'permit_empty|max_length[4000]',
        'currency' => 'required|valid_currency_code',
        'total_amount' => 'required|positive_money',
        'category_id' => 'permit_empty|is_natural_no_zero',
        'split_method' => 'required|valid_split_method',
        'frequency' => 'required|valid_recurring_frequency',
        'interval_value' => 'required|is_natural_no_zero',
        'starts_at' => 'required|valid_date[Y-m-d\\TH:i]',
        'ends_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'day_of_month' => 'permit_empty|greater_than_equal_to[1]|less_than_equal_to[31]',
        'custom_unit' => 'permit_empty|valid_recurring_custom_unit',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $recurringExpenseUpdate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'description' => 'permit_empty|max_length[4000]',
        'currency' => 'required|valid_currency_code',
        'total_amount' => 'required|positive_money',
        'category_id' => 'permit_empty|is_natural_no_zero',
        'split_method' => 'required|valid_split_method',
        'frequency' => 'required|valid_recurring_frequency',
        'interval_value' => 'required|is_natural_no_zero',
        'starts_at' => 'required|valid_date[Y-m-d\\TH:i]',
        'ends_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'day_of_month' => 'permit_empty|greater_than_equal_to[1]|less_than_equal_to[31]',
        'custom_unit' => 'permit_empty|valid_recurring_custom_unit',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $choreTemplateCreate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'description' => 'permit_empty|max_length[4000]',
        'assignment_mode' => 'required|valid_chore_assignment_mode',
        'fixed_assignee_user_id' => 'permit_empty|is_natural_no_zero',
        'rotation_anchor_user_id' => 'permit_empty|is_natural_no_zero',
        'points' => 'permit_empty|is_natural',
        'estimated_minutes' => 'permit_empty|is_natural',
        'is_active' => 'permit_empty|in_list[0,1,on]',
        'first_due_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'frequency' => 'permit_empty|valid_recurring_frequency',
        'interval_value' => 'permit_empty|is_natural_no_zero',
        'starts_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'ends_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'day_of_month' => 'permit_empty|greater_than_equal_to[1]|less_than_equal_to[31]',
        'custom_unit' => 'permit_empty|valid_recurring_custom_unit',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $choreTemplateUpdate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'description' => 'permit_empty|max_length[4000]',
        'assignment_mode' => 'required|valid_chore_assignment_mode',
        'fixed_assignee_user_id' => 'permit_empty|is_natural_no_zero',
        'rotation_anchor_user_id' => 'permit_empty|is_natural_no_zero',
        'points' => 'permit_empty|is_natural',
        'estimated_minutes' => 'permit_empty|is_natural',
        'is_active' => 'permit_empty|in_list[0,1,on]',
        'first_due_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'frequency' => 'permit_empty|valid_recurring_frequency',
        'interval_value' => 'permit_empty|is_natural_no_zero',
        'starts_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'ends_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
        'day_of_month' => 'permit_empty|greater_than_equal_to[1]|less_than_equal_to[31]',
        'custom_unit' => 'permit_empty|valid_recurring_custom_unit',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $choreOccurrenceFilters = [
        'status' => 'permit_empty|valid_chore_occurrence_status',
        'assigned_user_id' => 'permit_empty|is_natural_no_zero',
        'date' => 'permit_empty|valid_date[Y-m-d]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $choreOccurrenceCreate = [
        'due_at' => 'required|valid_date[Y-m-d\\TH:i]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $choreOccurrenceSkip = [
        'skip_reason' => 'required|min_length[3]|max_length[255]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $shoppingListCreate = [
        'name' => 'required|min_length[2]|max_length[120]',
        'is_default' => 'permit_empty|in_list[0,1,on]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $shoppingListUpdate = [
        'name' => 'required|min_length[2]|max_length[120]',
        'is_default' => 'permit_empty|in_list[0,1,on]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $shoppingItemCreate = [
        'name' => 'required|min_length[1]|max_length[160]',
        'quantity' => 'required|numeric|greater_than[0]',
        'unit' => 'permit_empty|max_length[32]',
        'category' => 'permit_empty|max_length[64]',
        'notes' => 'permit_empty|max_length[4000]',
        'priority' => 'required|valid_shopping_priority',
        'assigned_user_id' => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $shoppingItemUpdate = [
        'name' => 'required|min_length[1]|max_length[160]',
        'quantity' => 'required|numeric|greater_than[0]',
        'unit' => 'permit_empty|max_length[32]',
        'category' => 'permit_empty|max_length[64]',
        'notes' => 'permit_empty|max_length[4000]',
        'priority' => 'required|valid_shopping_priority',
        'assigned_user_id' => 'permit_empty|is_natural_no_zero',
        'position' => 'permit_empty|is_natural',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $shoppingBulkPurchase = [
        'mark_as' => 'required|in_list[purchased,unpurchased]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $shoppingConvertExpense = [
        'title' => 'required|min_length[3]|max_length[160]',
        'total_amount' => 'required|positive_money',
        'expense_date' => 'required|valid_date[Y-m-d]',
        'payer_user_id' => 'required|is_natural_no_zero',
        'category_id' => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $pinboardPostCreate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'body' => 'required|min_length[3]|max_length[65535]',
        'post_type' => 'required|valid_post_type',
        'is_pinned' => 'permit_empty|in_list[0,1,on]',
        'due_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $pinboardPostUpdate = [
        'title' => 'required|min_length[3]|max_length[160]',
        'body' => 'required|min_length[3]|max_length[65535]',
        'post_type' => 'required|valid_post_type',
        'is_pinned' => 'permit_empty|in_list[0,1,on]',
        'due_at' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
    ];

    /**
     * @var array<string, list<string>|string>
     */
    public array $pinboardCommentCreate = [
        'body' => 'required|min_length[2]|max_length[4000]',
    ];
}
