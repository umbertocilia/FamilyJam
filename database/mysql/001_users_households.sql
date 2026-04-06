SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(80) NULL,
    `last_name` VARCHAR(80) NULL,
    `display_name` VARCHAR(120) NOT NULL,
    `avatar_path` VARCHAR(255) NULL,
    `locale` VARCHAR(10) NOT NULL DEFAULT 'it',
    `theme` VARCHAR(16) NOT NULL DEFAULT 'system',
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'Europe/Rome',
    `status` VARCHAR(24) NOT NULL DEFAULT 'active',
    `email_verified_at` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `households` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `avatar_path` VARCHAR(255) NULL,
    `base_currency` CHAR(3) NOT NULL DEFAULT 'EUR',
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'Europe/Rome',
    `simplify_debts` TINYINT(1) NOT NULL DEFAULT 1,
    `chore_scoring_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_households_slug` (`slug`),
    KEY `idx_households_creator_archived` (`created_by`, `is_archived`),
    CONSTRAINT `fk_households_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_preferences` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `default_household_id` BIGINT UNSIGNED NULL,
    `notification_preferences_json` JSON NULL,
    `dashboard_preferences_json` JSON NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_preferences_user` (`user_id`),
    CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_user_preferences_default_household` FOREIGN KEY (`default_household_id`) REFERENCES `households` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `household_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NOT NULL,
    `locale` VARCHAR(10) NOT NULL DEFAULT 'it',
    `week_starts_on` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `date_format` VARCHAR(20) NOT NULL DEFAULT 'd/m/Y',
    `time_format` VARCHAR(10) NOT NULL DEFAULT '24h',
    `notification_settings_json` JSON NULL,
    `module_settings_json` JSON NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_household_settings_household` (`household_id`),
    CONSTRAINT `fk_household_settings_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
