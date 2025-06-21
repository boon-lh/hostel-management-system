-- Room Constraints Update Script
-- This script adds important constraints to the rooms table to ensure data integrity
-- Created: June 22, 2025

-- Add comments explaining that this is important for the hostel management system
ALTER TABLE `rooms` COMMENT = 'Contains all hostel room information with availability status and constraints';

-- Make sure room_number is unique within each block
ALTER TABLE `rooms` 
ADD CONSTRAINT `unique_room_per_block` 
UNIQUE (`room_number`, `block_id`);

-- Ensure the availability_status is one of the predefined values
ALTER TABLE `rooms` 
MODIFY COLUMN `availability_status` 
ENUM('Available', 'Occupied', 'Reserved', 'Under Maintenance', 'Pending Confirmation') 
NOT NULL DEFAULT 'Available';

-- Add constraint to ensure capacity is a positive number
ALTER TABLE `rooms` 
ADD CONSTRAINT `check_positive_capacity` 
CHECK (`capacity` > 0);

-- Add foreign key constraint to ensure block_id exists in hostel_blocks table
-- First check if the constraint already exists, if not then add it
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'rooms' 
    AND CONSTRAINT_NAME = 'fk_room_block'
);

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE `rooms` 
    ADD CONSTRAINT `fk_room_block` 
    FOREIGN KEY (`block_id`) 
    REFERENCES `hostel_blocks`(`id`) 
    ON UPDATE CASCADE 
    ON DELETE RESTRICT',
    'SELECT \'Foreign key constraint already exists\' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the room type is one of the valid types
ALTER TABLE `rooms` 
MODIFY COLUMN `type` 
ENUM('Single', 'Double', 'Triple', 'Quad', 'Suite') 
NOT NULL DEFAULT 'Single';

-- Add index on availability_status for faster searches
CREATE INDEX IF NOT EXISTS `idx_room_availability` 
ON `rooms` (`availability_status`);

-- Add timestamp columns if they don't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'rooms' 
    AND COLUMN_NAME = 'updated_at'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `rooms` 
    ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
    ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT \'Timestamp columns already exist\' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add a comment explaining that the script completed
-- This is useful for debugging if needed
SELECT 'Room constraints update completed successfully' AS 'Status';
