-- Migration v4 - adds the staff role and a demo staff account
USE `technical_inventory`;

ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','staff','student') NOT NULL DEFAULT 'student';

INSERT IGNORE INTO `users` (`user_id`, `name`, `email`, `course`, `password`, `role`) VALUES
  ('STAFF-001', 'Technical Staff', 'staff@aclc.edu.ph', 'Staff',
   '$2y$10$.XzU0qLlQRiNOc251TIXrOyAcn3wd4Os.hBI3JpcML/e/m.pL4ebO', 'staff');
