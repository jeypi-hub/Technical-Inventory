-- =============================================================
--  Migration v8:
--  - Adds dedicated 'faculty' role to users.role ENUM
--  - Updates seeded FAC-001 account to role = 'faculty'
-- =============================================================

USE `technical_inventory`;

ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','staff','faculty','student')
  NOT NULL DEFAULT 'student';

UPDATE `users`
  SET `role` = 'faculty'
  WHERE `user_id` = 'FAC-001';
