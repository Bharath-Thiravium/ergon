-- Persistent login tokens for Capacitor mobile app
-- Run once against the production database

CREATE TABLE IF NOT EXISTS user_tokens (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL COMMENT 'SHA-256 hex of the raw token',
    device_hint VARCHAR(255)    DEFAULT NULL COMMENT 'Optional UA snippet for audit',
    expires_at  DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_token_hash (token_hash),
    KEY     idx_user_id   (user_id),
    KEY     idx_expires   (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
