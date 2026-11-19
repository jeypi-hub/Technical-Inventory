-- Migration v3 - adds quantity to borrow requests
USE `technical_inventory`;

ALTER TABLE `borrow_requests`
  ADD COLUMN `quantity` INT NOT NULL DEFAULT 1 AFTER `teacher`;
