-- =====================================================================
-- ZPGC Services — Database setup (run this ONE file in phpMyAdmin)
-- =====================================================================
-- Migrates accounts from users_db → zpgc_services_db and creates
-- tickets + messages tables.
--
-- HOW TO RUN:
--   1. XAMPP: start Apache + MySQL
--   2. http://localhost/phpmyadmin → SQL tab
--   3. Paste this entire file → Go
--   4. Log out of the website, log in again, and test
-- =====================================================================

CREATE DATABASE IF NOT EXISTS zpgc_services_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE zpgc_services_db;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- 1. Users — copy accounts from users_db
--
-- We define the table explicitly as InnoDB with a PRIMARY KEY on id.
-- (CREATE TABLE ... LIKE users_db.users often copies MyISAM, which
-- breaks foreign keys on tickets/messages with errno 150.)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name  VARCHAR(100) NOT NULL,
    last_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        VARCHAR(20)  NOT NULL,
    status      VARCHAR(20)  NOT NULL DEFAULT 'inactive',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, first_name, last_name, email, password, role, status)
SELECT id, first_name, last_name, email, password, role, status
FROM users_db.users;

-- ---------------------------------------------------------------------
-- 2. Tickets
-- ---------------------------------------------------------------------
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
    KEY idx_tickets_assigned (assigned_to),
    CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_assigned FOREIGN KEY (assigned_to) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Messages (Mailbox — wired up in a later step)
-- ---------------------------------------------------------------------
CREATE TABLE messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id   INT UNSIGNED NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,
    message     TEXT NOT NULL,
    sent_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_ticket (ticket_id),
    CONSTRAINT fk_messages_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. OPTIONAL — copy old ticket rows from users_db (uncomment if needed)
-- ---------------------------------------------------------------------
-- INSERT INTO tickets SELECT * FROM users_db.tickets;
-- INSERT INTO messages SELECT * FROM users_db.messages;

-- ---------------------------------------------------------------------
-- 5. Verify
-- ---------------------------------------------------------------------
SELECT 'users'    AS tbl, COUNT(*) AS row_count FROM users
UNION ALL
SELECT 'tickets', COUNT(*) FROM tickets
UNION ALL
SELECT 'messages', COUNT(*) FROM messages;

-- CLEANUP (run separately after the app works):
-- DROP DATABASE users_db;
