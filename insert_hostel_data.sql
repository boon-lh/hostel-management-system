-- Insert Hostel Blocks and Rooms based on the Hostel Block Management interface
-- Run this in phpMyAdmin SQL tab

USE hostel_management;

-- Insert the 4 hostel blocks 
INSERT INTO `hostel_blocks` (`block_name`, `gender_restriction`, `nationality_restriction`, `description`, `created_at`) VALUES
('Block A', 'Male', 'Local', 'Hostel block for local male students with standard facilities.', NOW()),
('Block B', 'Female', 'Local', 'Hostel block for local female students with standard facilities.', NOW()),
('Block C', 'Male', 'International', 'Hostel block for international male students with cultural integration facilities.', NOW()),
('Block D', 'Female', 'International', 'Hostel block for international female students with cultural integration facilities.', NOW());

-- Insert 10 rooms for each block (40 rooms total)
-- Block A Rooms (Local Male Students)
INSERT INTO `rooms` (`block_id`, `room_number`, `type`, `capacity`, `price`, `features`, `availability_status`, `created_at`) VALUES
(1, 'A-101', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-102', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-103', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-104', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-105', 'Triple', 3, 500.00, 'Air-conditioned, Study desk, Triple bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-106', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-107', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-108', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-109', 'Triple', 3, 500.00, 'Air-conditioned, Study desk, Triple bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(1, 'A-110', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW());

-- Block B Rooms (Local Female Students)
INSERT INTO `rooms` (`block_id`, `room_number`, `type`, `capacity`, `price`, `features`, `availability_status`, `created_at`) VALUES
(2, 'B-101', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-102', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-103', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-104', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-105', 'Triple', 3, 500.00, 'Air-conditioned, Study desk, Triple bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-106', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-107', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-108', 'Single', 1, 800.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-109', 'Triple', 3, 500.00, 'Air-conditioned, Study desk, Triple bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(2, 'B-110', 'Double', 2, 600.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW());

-- Block C Rooms (International Male Students)
INSERT INTO `rooms` (`block_id`, `room_number`, `type`, `capacity`, `price`, `features`, `availability_status`, `created_at`) VALUES
(3, 'C-101', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(3, 'C-102', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(3, 'C-103', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(3, 'C-104', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(3, 'C-105', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(3, 'C-106', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(3, 'C-107', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(3, 'C-108', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(3, 'C-109', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(3, 'C-110', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW());

-- Block D Rooms (International Female Students)
INSERT INTO `rooms` (`block_id`, `room_number`, `type`, `capacity`, `price`, `features`, `availability_status`, `created_at`) VALUES
(4, 'D-101', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(4, 'D-102', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(4, 'D-103', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(4, 'D-104', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(4, 'D-105', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(4, 'D-106', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(4, 'D-107', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(4, 'D-108', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW()),
(4, 'D-109', 'Single', 1, 1000.00, 'Air-conditioned, Study desk, Single bed, Wardrobe, Private bathroom', 'Available', NOW()),
(4, 'D-110', 'Double', 2, 750.00, 'Air-conditioned, Study desk, Double bed, Wardrobe, Shared bathroom', 'Available', NOW());

-- Verify the data was inserted
SELECT 'Hostel Blocks' as Table_Name, COUNT(*) as Total_Records FROM hostel_blocks
UNION ALL
SELECT 'Rooms' as Table_Name, COUNT(*) as Total_Records FROM rooms;

-- Show summary by block
SELECT 
    hb.block_name,
    hb.gender_restriction,
    hb.nationality_restriction,
    COUNT(r.id) as total_rooms,
    SUM(CASE WHEN r.type = 'Single' THEN 1 ELSE 0 END) as single_rooms,
    SUM(CASE WHEN r.type = 'Double' THEN 1 ELSE 0 END) as double_rooms,
    SUM(CASE WHEN r.type = 'Triple' THEN 1 ELSE 0 END) as triple_rooms
FROM hostel_blocks hb
LEFT JOIN rooms r ON hb.id = r.block_id
GROUP BY hb.id, hb.block_name, hb.gender_restriction, hb.nationality_restriction
ORDER BY hb.block_name;
