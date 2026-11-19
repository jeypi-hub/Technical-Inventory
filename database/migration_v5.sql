USE `technical_inventory`;

ALTER TABLE `users`
  ADD COLUMN `language` VARCHAR(8) NOT NULL DEFAULT 'en' AFTER `role`,
  ADD COLUMN `dark_mode` TINYINT(1) NOT NULL DEFAULT 0 AFTER `language`,
  ADD COLUMN `compact_mode` TINYINT(1) NOT NULL DEFAULT 0 AFTER `dark_mode`;
