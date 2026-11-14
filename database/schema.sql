-- Schéma de la base Koabana (MySQL 8.4)
-- Colonnes déduites de Koabana\Model\Entity\UserEntity (propriété camelCase -> colonne snake_case).
-- Installation : mysql -u <user> -p <dbname> < database/schema.sql
-- Les tests utilisent un équivalent SQLite : tests/Integration/UserDatabase.php

CREATE TABLE IF NOT EXISTS `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `firstname` VARCHAR(100) NOT NULL,
    `lastname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `token` CHAR(32) NULL,
    `token_expires_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_email` (`email`),
    UNIQUE KEY `uniq_user_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
