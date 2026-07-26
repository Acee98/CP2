-- ZPGC_SETUP_V3_NO_FK
-- Import this file in phpMyAdmin: Import tab -> Choose file -> setup.sql -> Go
-- Do NOT copy/paste from chat. Search this file for: ZPGC_SETUP_V3_NO_FK

CREATE DATABASE IF NOT EXISTS zpgc_services_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE zpgc_services_db;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name  VARCHAR(255) NOT NULL,
    last_name   VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('user', 'admin', 'techn') NOT NULL,
    status      VARCHAR(10)  NOT NULL DEFAULT 'active',
    PRIMARY KEY (id),
    UNIQUE KEY email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users (id, first_name, last_name, email, password, role, status)
SELECT id, first_name, last_name, email, password, role, status
FROM users_db.users;

CREATE TABLE tickets (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    subject         VARCHAR(255) NOT NULL,
    description     TEXT NOT NULL,
    category        ENUM('hardware', 'software', 'network', 'account', 'other') NOT NULL,
    severity        ENUM('low', 'moderate', 'critical') NULL DEFAULT NULL,
    status          ENUM('pending', 'ongoing', 'processing', 'resolved') NOT NULL DEFAULT 'pending',
    assigned_to     INT UNSIGNED NULL DEFAULT NULL,
    ai_category     VARCHAR(50) NULL DEFAULT NULL,
    ai_severity     VARCHAR(20) NULL DEFAULT NULL,
    ai_suggestion   TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tickets_user (user_id),
    KEY idx_tickets_status (status),
    KEY idx_tickets_assigned (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id   INT UNSIGNED NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,
    message     TEXT NOT NULL,
    sent_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_ticket (ticket_id),
    KEY idx_messages_sender (sender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'users'    AS tbl, COUNT(*) AS row_count FROM users
UNION ALL
SELECT 'tickets', COUNT(*) FROM tickets
UNION ALL
SELECT 'messages', COUNT(*) FROM messages;
