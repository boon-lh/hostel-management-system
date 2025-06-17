-- Delete all hostel blocks data
-- Run this in phpMyAdmin SQL tab

USE hostel_management;

-- First, we need to handle any foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Delete all records from hostel_blocks table
DELETE FROM hostel_blocks;

-- Reset the auto-increment counter to start from 1 again
ALTER TABLE hostel_blocks AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify the table is empty
SELECT * FROM hostel_blocks;
