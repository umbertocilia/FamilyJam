CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `household_id` BIGINT UNSIGNED NULL,
    `type` VARCHAR(64) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `body` TEXT NULL,
    `data_json` JSON NULL,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_read_created` (`user_id`, `read_at`, `created_at`),
    KEY `idx_notifications_household_created` (`household_id`, `created_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_notifications_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `household_id` BIGINT UNSIGNED NULL,
    `actor_user_id` BIGINT UNSIGNED NULL,
    `entity_type` VARCHAR(64) NOT NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(64) NOT NULL,
    `before_json` JSON NULL,
    `after_json` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_household_created` (`household_id`, `created_at`),
    KEY `idx_audit_logs_actor_created` (`actor_user_id`, `created_at`),
    KEY `idx_audit_logs_entity_created` (`entity_type`, `entity_id`, `created_at`),
    KEY `idx_audit_logs_action_created` (`action`, `created_at`),
    CONSTRAINT `fk_audit_logs_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON UPDATE SET NULL ON DELETE SET NULL,
    CONSTRAINT `fk_audit_logs_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON UPDATE SET NULL ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
