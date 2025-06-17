-- Alternative method: Drop all tables individually instead of dropping the database
-- Run this in phpMyAdmin SQL tab

USE hostel_management;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables (order doesn't matter when foreign key checks are disabled)
DROP TABLE IF EXISTS `complaint_status_history`;
DROP TABLE IF EXISTS `maintenance_assignments`;
DROP TABLE IF EXISTS `request_status_history`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `refunds`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `hostel_registrations`;
DROP TABLE IF EXISTS `emergency_contacts`;
DROP TABLE IF EXISTS `service_requests`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `hostel_blocks`;
DROP TABLE IF EXISTS `maintenance_staff`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `visitors`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `admins`;

-- Verify all tables are dropped
SHOW TABLES;

SET FOREIGN_KEY_CHECKS = 1;

-- Now you can import your database_setup.sql file using the Import tab
