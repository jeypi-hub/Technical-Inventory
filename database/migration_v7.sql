-- =============================================================
--  Migration v7:
--  - Adds profile_picture to users
--  - Adds 'pending' and 'overdue' statuses to equipment & borrow_requests
--  - Adds duration_minutes (30-min intervals up to 4 hours), reserved_equipment_ids,
--    qr_token, borrowed_at, and due_at to borrow_requests
--  - Adds 'overdue' and 'lost' notification types
--  - Seeds demo Faculty account (FAC-001 / password123)
-- =============================================================

USE `technical_inventory`;

SET @db_name = DATABASE();

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL AFTER `email`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `equipment`
  MODIFY COLUMN `status` ENUM('available','pending','borrowed','overdue','maintenance','lost')
  NOT NULL DEFAULT 'available';

ALTER TABLE `borrow_requests`
  MODIFY COLUMN `status` ENUM('pending','approved','borrowed','overdue','rejected','returned','lost','broken')
  NOT NULL DEFAULT 'pending';

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'reserved_equipment_ids');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `borrow_requests` ADD COLUMN `reserved_equipment_ids` TEXT DEFAULT NULL AFTER `equipment_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'duration_minutes');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `borrow_requests` ADD COLUMN `duration_minutes` INT NOT NULL DEFAULT 60 AFTER `quantity`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'qr_token');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `borrow_requests` ADD COLUMN `qr_token` VARCHAR(64) DEFAULT NULL UNIQUE AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'borrowed_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `borrow_requests` ADD COLUMN `borrowed_at` DATETIME DEFAULT NULL AFTER `approved_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'borrow_requests' AND COLUMN_NAME = 'due_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `borrow_requests` ADD COLUMN `due_at` DATETIME DEFAULT NULL AFTER `borrowed_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `notifications`
  MODIFY COLUMN `type` ENUM('request','approved','rejected','returned','overdue','lost','system')
  NOT NULL;

INSERT IGNORE INTO `users` (`user_id`, `name`, `email`, `course`, `password`, `role`) VALUES
  ('FAC-001', 'Faculty Member', 'faculty@aclc.edu.ph', 'Faculty - IT Dept',
   '$2b$10$dEygJtmiYtcLreOPSKocv.22ZMdli2i4GnDsZr.ru96EnaFFKH9Re', 'student');
