-- Run once in phpMyAdmin on an existing zpgc_services_db install.
-- Clears manual/default priority until OpenAI assigns ai_severity.
USE zpgc_services_db;

ALTER TABLE tickets
    MODIFY severity ENUM('low', 'moderate', 'critical') NULL DEFAULT NULL;

UPDATE tickets
SET severity = NULL
WHERE ai_severity IS NULL;
