-- =============================================================
--  Migration v2 — adds email column + student cancel feature
--  Run this ONLY if you already imported v1 of technical_inventory.sql
--  (fresh installs of technical_inventory.sql already include these)
-- =============================================================
USE `technical_inventory`;

ALTER TABLE `users`
  ADD COLUMN `email` VARCHAR(160) DEFAULT NULL AFTER `name`;

UPDATE `users` SET `email` = 'admin@aclc.edu.ph' WHERE `user_id` = 'ADMIN-001';
UPDATE `users` SET `email` = 'user1@aclc.edu.ph' WHERE `user_id` = 'C24-01-1524-MAN121';
UPDATE `users` SET `email` = 'user2@aclc.edu.ph' WHERE `user_id` = 'w';
