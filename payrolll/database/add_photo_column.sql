-- Add photo column to employees table
ALTER TABLE `employees` 
ADD COLUMN `photo` VARCHAR(255) DEFAULT NULL COMMENT 'Employee photo filename' AFTER `pagibig_number`;

