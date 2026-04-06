INSERT INTO `permissions` (`code`, `name`, `module`, `description`, `created_at`, `updated_at`) VALUES
('manage_household', 'Manage household', 'households', 'Gestisce ownership, ciclo di vita e amministrazione dello spazio.', NOW(), NOW()),
('manage_members', 'Manage members', 'memberships', 'Invita, sospende e aggiorna i membri del gruppo.', NOW(), NOW()),
('manage_roles', 'Manage roles', 'authorization', 'Configura ruoli household-specific e relative grant list.', NOW(), NOW()),
('create_expense', 'Create expense', 'expenses', 'Registra nuove spese condivise.', NOW(), NOW()),
('edit_any_expense', 'Edit any expense', 'expenses', 'Modifica qualunque spesa del tenant.', NOW(), NOW()),
('edit_own_expense', 'Edit own expense', 'expenses', 'Modifica soltanto le spese create dal membro.', NOW(), NOW()),
('delete_expense', 'Delete expense', 'expenses', 'Rimuove o annulla spese condivise.', NOW(), NOW()),
('add_settlement', 'Add settlement', 'balances', 'Registra rimborsi e regolazioni di saldo.', NOW(), NOW()),
('manage_chores', 'Manage chores', 'chores', 'Crea e pianifica faccende, rotazioni e ricorrenze.', NOW(), NOW()),
('complete_chore', 'Complete chore', 'chores', 'Marca una faccenda come completata.', NOW(), NOW()),
('manage_shopping', 'Manage shopping', 'shopping', 'Gestisce liste della spesa condivise.', NOW(), NOW()),
('manage_pinboard', 'Manage pinboard', 'pinboard', 'Pubblica, aggiorna e modera contenuti della bacheca.', NOW(), NOW()),
('manage_settings', 'Manage settings', 'settings', 'Aggiorna preferenze operative del workspace.', NOW(), NOW()),
('view_reports', 'View reports', 'reports', 'Consulta dashboard, riepiloghi e analytics household.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`module` = VALUES(`module`),
`description` = VALUES(`description`),
`updated_at` = VALUES(`updated_at`);

INSERT INTO `roles` (`household_id`, `scope_household_id`, `code`, `name`, `description`, `is_system`, `is_assignable`, `created_at`, `updated_at`) VALUES
(NULL, 0, 'owner', 'Owner', 'Controllo completo dello spazio household e dei permessi.', 1, 0, NOW(), NOW()),
(NULL, 0, 'admin', 'Admin', 'Gestione operativa del workspace con limiti sulla governance finale.', 1, 1, NOW(), NOW()),
(NULL, 0, 'member', 'Member', 'Contribuisce a spese, bacheca, chores e shopping condivisi.', 1, 1, NOW(), NOW()),
(NULL, 0, 'guest', 'Guest', 'Accesso limitato a task e strumenti collaborativi essenziali.', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`description` = VALUES(`description`),
`is_system` = VALUES(`is_system`),
`is_assignable` = VALUES(`is_assignable`),
`updated_at` = VALUES(`updated_at`);

DELETE rp
FROM `role_permissions` rp
INNER JOIN `roles` r ON r.id = rp.role_id
WHERE r.household_id IS NULL
  AND r.code IN ('owner', 'admin', 'member', 'guest');

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
INNER JOIN `permissions` p ON p.code IN (
    'manage_household',
    'manage_members',
    'manage_roles',
    'create_expense',
    'edit_any_expense',
    'edit_own_expense',
    'delete_expense',
    'add_settlement',
    'manage_chores',
    'complete_chore',
    'manage_shopping',
    'manage_pinboard',
    'manage_settings',
    'view_reports'
)
WHERE r.household_id IS NULL
  AND r.code = 'owner';

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
INNER JOIN `permissions` p ON p.code IN (
    'manage_members',
    'manage_roles',
    'create_expense',
    'edit_any_expense',
    'edit_own_expense',
    'delete_expense',
    'add_settlement',
    'manage_chores',
    'complete_chore',
    'manage_shopping',
    'manage_pinboard',
    'manage_settings',
    'view_reports'
)
WHERE r.household_id IS NULL
  AND r.code = 'admin';

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
INNER JOIN `permissions` p ON p.code IN (
    'create_expense',
    'edit_own_expense',
    'add_settlement',
    'complete_chore',
    'manage_shopping',
    'manage_pinboard',
    'view_reports'
)
WHERE r.household_id IS NULL
  AND r.code = 'member';

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
INNER JOIN `permissions` p ON p.code IN (
    'complete_chore',
    'manage_shopping',
    'manage_pinboard'
)
WHERE r.household_id IS NULL
  AND r.code = 'guest';

INSERT INTO `expense_categories` (`household_id`, `scope_household_id`, `code`, `name`, `color`, `icon`, `is_system`, `sort_order`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(NULL, 0, 'groceries', 'Groceries', '#1f9d55', 'basket', 1, 10, 1, NULL, NOW(), NOW()),
(NULL, 0, 'utilities', 'Utilities', '#2563eb', 'bolt', 1, 20, 1, NULL, NOW(), NOW()),
(NULL, 0, 'rent', 'Rent', '#7c3aed', 'home', 1, 30, 1, NULL, NOW(), NOW()),
(NULL, 0, 'internet', 'Internet', '#0f766e', 'wifi', 1, 40, 1, NULL, NOW(), NOW()),
(NULL, 0, 'cleaning', 'Cleaning', '#d97706', 'sparkles', 1, 50, 1, NULL, NOW(), NOW()),
(NULL, 0, 'transport', 'Transport', '#dc2626', 'car', 1, 60, 1, NULL, NOW(), NOW()),
(NULL, 0, 'entertainment', 'Entertainment', '#db2777', 'ticket', 1, 70, 1, NULL, NOW(), NOW()),
(NULL, 0, 'misc', 'Misc', '#6b7280', 'dots', 1, 80, 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`color` = VALUES(`color`),
`icon` = VALUES(`icon`),
`sort_order` = VALUES(`sort_order`),
`is_active` = VALUES(`is_active`),
`updated_at` = VALUES(`updated_at`);
