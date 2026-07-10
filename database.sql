-- Profile system schema
-- Import this into your MySQL database before using the site.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username          VARCHAR(20) NOT NULL UNIQUE,
    email             VARCHAR(191) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    display_name      VARCHAR(64) DEFAULT NULL,
    discord_id        VARCHAR(25) DEFAULT NULL,
    contact_email     VARCHAR(191) DEFAULT NULL,
    background_image  VARCHAR(255) DEFAULT NULL,
    background_url    VARCHAR(500) DEFAULT NULL,
    text_color        VARCHAR(7) DEFAULT NULL,
    counter_color     VARCHAR(7) DEFAULT NULL,
    hit_count         INT UNSIGNED NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS socials (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    platform    VARCHAR(32) NOT NULL,
    label       VARCHAR(64) NOT NULL,
    icon        VARCHAR(64) NOT NULL,
    css_class   VARCHAR(64) NOT NULL DEFAULT '',
    url         VARCHAR(500) NOT NULL,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_socials_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visitor_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    ip_hash     CHAR(64) NOT NULL,
    visit_date  DATE NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_visit_per_day (user_id, ip_hash, visit_date),
    CONSTRAINT fk_visitor_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
