-- Run this once in your LifeSync database.
-- Stores password-reset requests separately from `users` so tokens
-- can expire / be reused without touching the main user record.

CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(190) NOT NULL,
    token_hash  VARCHAR(64)  NOT NULL,   -- sha256 hash of the token sent by email
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
