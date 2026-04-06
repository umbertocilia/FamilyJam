CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NULL,
    `scope_household_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `code` VARCHAR(64) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `is_assignable` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_scope_code` (`scope_household_id`, `code`),
    KEY `idx_roles_household_system` (`household_id`, `is_system`),
    CONSTRAINT `fk_roles_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(64) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `module` VARCHAR(64) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_code` (`code`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_role_permissions_role_permission` (`role_id`, `permission_id`),
    CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `household_memberships` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `invited_by_user_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(24) NOT NULL DEFAULT 'active',
    `nickname` VARCHAR(120) NULL,
    `joined_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_household_memberships_household_user` (`household_id`, `user_id`),
    KEY `idx_household_memberships_household_status` (`household_id`, `status`),
    CONSTRAINT `fk_household_memberships_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_household_memberships_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_household_memberships_invited_by` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `membership_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `membership_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `assigned_by_user_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_membership_roles_membership_role` (`membership_id`, `role_id`),
    KEY `idx_membership_roles_role` (`role_id`),
    CONSTRAINT `fk_membership_roles_membership` FOREIGN KEY (`membership_id`) REFERENCES `household_memberships` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_membership_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_membership_roles_assigned_by` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invitations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `role_id` BIGINT UNSIGNED NULL,
    `token_hash` CHAR(64) NOT NULL,
    `invited_by_user_id` BIGINT UNSIGNED NULL,
    `message` VARCHAR(500) NULL,
    `expires_at` DATETIME NULL,
    `accepted_at` DATETIME NULL,
    `revoked_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_invitations_token_hash` (`token_hash`),
    KEY `idx_invitations_household_email` (`household_id`, `email`),
    KEY `idx_invitations_expires_at` (`expires_at`),
    CONSTRAINT `fk_invitations_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_invitations_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE SET NULL ON DELETE SET NULL,
    CONSTRAINT `fk_invitations_invited_by` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
