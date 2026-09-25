-- =============================================================
--  ACLC College — Technical Equipment Inventory System
--  MySQL schema + seed data
--  Import via phpMyAdmin (create DB `technical_inventory` first)
--  or: mysql -u root technical_inventory < technical_inventory.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS `technical_inventory`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `technical_inventory`;

-- -------------------------------------------------------------
--  users : holds both admins and students
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    VARCHAR(64)  NOT NULL UNIQUE,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(160) DEFAULT NULL,
  `course`     VARCHAR(80)  DEFAULT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','student') NOT NULL DEFAULT 'student',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  equipment
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(120) NOT NULL,
  `serial_number` VARCHAR(40)  NOT NULL UNIQUE,
  `image`         VARCHAR(255) DEFAULT NULL,
  `status`        ENUM('available','borrowed','maintenance','lost') NOT NULL DEFAULT 'available',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_equipment_status` (`status`)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  borrow_requests
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `borrow_requests`;
CREATE TABLE `borrow_requests` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `equipment_id` INT NOT NULL,
  `purpose`      VARCHAR(160) NOT NULL,
  `classroom`    VARCHAR(80)  NOT NULL,
  `teacher`      VARCHAR(120) NOT NULL,
  `status`       ENUM('pending','approved','rejected','returned','lost','broken') NOT NULL DEFAULT 'pending',
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at`  DATETIME DEFAULT NULL,
  `returned_at`  DATETIME DEFAULT NULL,
  `condition_on_return` ENUM('good','damaged','lost') DEFAULT NULL,
  CONSTRAINT `fk_br_user`  FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_br_equip` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
  INDEX `idx_br_status` (`status`),
  INDEX `idx_br_user`   (`user_id`),
  INDEX `idx_br_equip`  (`equipment_id`)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  notifications
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `type`       ENUM('request','approved','rejected','returned','system') NOT NULL,
  `message`    VARCHAR(255) NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `related_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB;

-- =============================================================
--  SEED DATA
--  All demo passwords are: password123
--  Hash generated with password_hash('password123', PASSWORD_DEFAULT)
-- =============================================================
INSERT INTO `users` (`user_id`, `name`, `email`, `course`, `password`, `role`) VALUES
  ('ADMIN-001',           'Technical Admin', 'admin@aclc.edu.ph', 'Administrator',
   '$2b$10$dEygJtmiYtcLreOPSKocv.22ZMdli2i4GnDsZr.ru96EnaFFKH9Re', 'admin'),
  ('C24-01-1524-MAN121',  'User 1', 'user1@aclc.edu.ph', 'BSIT',
   '$2b$10$dEygJtmiYtcLreOPSKocv.22ZMdli2i4GnDsZr.ru96EnaFFKH9Re', 'student'),
  ('C24-01-9654-MAN121',  'User 2', 'user2@aclc.edu.ph', 'BSCS',
   '$2b$10$dEygJtmiYtcLreOPSKocv.22ZMdli2i4GnDsZr.ru96EnaFFKH9Re', 'student');

INSERT INTO `equipment` (`name`, `serial_number`, `image`, `status`) VALUES
  ('HDMI',       'SN-3D4E75F0', NULL, 'available'),
  ('Cable Wire', 'SN-F536F84D', NULL, 'available'),
  ('Projector',  'SN-A1B2C3D4', NULL, 'available');
