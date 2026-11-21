USE `technical_inventory`;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `actor_id`      INT DEFAULT NULL,
  `actor_user_id` VARCHAR(64) DEFAULT NULL,
  `actor_name`    VARCHAR(120) DEFAULT NULL,
  `actor_role`    VARCHAR(16) DEFAULT NULL,
  `action`        VARCHAR(80) NOT NULL,
  `entity_type`   VARCHAR(40) DEFAULT NULL,
  `entity_id`     VARCHAR(80) DEFAULT NULL,
  `details`       TEXT DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_created` (`created_at`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_actor` (`actor_user_id`)
) ENGINE=InnoDB;
