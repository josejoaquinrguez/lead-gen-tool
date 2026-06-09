CREATE DATABASE IF NOT EXISTS lead_gen_tool
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lead_gen_tool;

CREATE TABLE IF NOT EXISTS search_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  postal_code VARCHAR(10) NOT NULL,
  keyword VARCHAR(120) NOT NULL,
  filter_name VARCHAR(60) NOT NULL DEFAULT 'all',
  total_results INT UNSIGNED NOT NULL DEFAULT 0,
  cached TINYINT(1) NOT NULL DEFAULT 0,
  stats_json LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_search_runs_lookup (postal_code, keyword, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS businesses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_hash CHAR(40) NOT NULL,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(120) NULL,
  address VARCHAR(500) NULL,
  city VARCHAR(160) NULL,
  postal_code VARCHAR(10) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(180) NULL,
  website VARCHAR(500) NULL,
  website_is_doubtful TINYINT(1) NOT NULL DEFAULT 0,
  instagram VARCHAR(500) NULL,
  facebook VARCHAR(500) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  score INT UNSIGNED NULL,
  level_name VARCHAR(60) NULL,
  issues_json LONGTEXT NULL,
  audit_json LONGTEXT NULL,
  raw_json LONGTEXT NULL,
  first_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_business_hash (business_hash),
  INDEX idx_businesses_score (score),
  INDEX idx_businesses_postal_category (postal_code, category),
  INDEX idx_businesses_contact (phone, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_run_businesses (
  search_run_id BIGINT UNSIGNED NOT NULL,
  business_id BIGINT UNSIGNED NOT NULL,
  position_number INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (search_run_id, business_id),
  CONSTRAINT fk_srb_search_run
    FOREIGN KEY (search_run_id) REFERENCES search_runs(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_srb_business
    FOREIGN KEY (business_id) REFERENCES businesses(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
