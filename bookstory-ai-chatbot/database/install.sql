-- Run this SQL in phpMyAdmin after selecting your bookstore database.
-- These are NEW chatbot tables; this script does not modify your products/users/orders tables.

CREATE TABLE IF NOT EXISTS ai_book_interactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_key CHAR(64) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    event_type ENUM('view','click','cart','purchase','favorite','search') NOT NULL DEFAULT 'view',
    query_text VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ai_interactions_visitor_time (visitor_key, created_at),
    KEY idx_ai_interactions_user_time (user_id, created_at),
    KEY idx_ai_interactions_product (product_id),
    KEY idx_ai_interactions_event_time (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_book_chat_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_key CHAR(64) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    role ENUM('user','assistant') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ai_chat_visitor_time (visitor_key, created_at),
    KEY idx_ai_chat_user_time (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
