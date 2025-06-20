-- Add a constraint to ensure a student can only have one active registration
-- First, let's update the existing registrations that might be in Cancelled or Rejected status
UPDATE hostel_registrations 
SET status = 'Expired' 
WHERE status NOT IN ('Pending', 'Approved', 'Checked In') 
  AND payment_status = 'Unpaid';

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS check_student_active_registration;
DROP TRIGGER IF EXISTS check_student_active_registration_update;

-- Create a trigger to prevent a student from having multiple active registrations
DELIMITER //
CREATE TRIGGER check_student_active_registration BEFORE INSERT ON hostel_registrations
FOR EACH ROW
BEGIN
    DECLARE active_count INT;
    
    -- Count active registrations for this student
    SELECT COUNT(*) INTO active_count 
    FROM hostel_registrations 
    WHERE student_id = NEW.student_id 
      AND status IN ('Pending', 'Approved', 'Checked In');
    
    -- If there's already an active registration, prevent the insert
    IF active_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Each student can only have one active room registration.';
    END IF;
END //
DELIMITER ;

-- Create a trigger to prevent updates that would result in multiple active registrations
DELIMITER //
CREATE TRIGGER check_student_active_registration_update BEFORE UPDATE ON hostel_registrations
FOR EACH ROW
BEGIN
    DECLARE active_count INT;
    
    -- Only check if we're changing the status to an active one
    IF NEW.status IN ('Pending', 'Approved', 'Checked In') AND 
       (OLD.status NOT IN ('Pending', 'Approved', 'Checked In') OR OLD.student_id != NEW.student_id) THEN
       
        -- Count active registrations for this student (excluding the current one being updated)
        SELECT COUNT(*) INTO active_count 
        FROM hostel_registrations 
        WHERE student_id = NEW.student_id 
          AND status IN ('Pending', 'Approved', 'Checked In')
          AND id != NEW.id;
        
        -- If there's already an active registration, prevent the update
        IF active_count > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Each student can only have one active room registration.';
        END IF;
    END IF;
END //
DELIMITER ;

-- Add a note in the system to indicate that one student can only have one active registration
INSERT INTO announcements (title, content, is_active)
VALUES (
    'Room Registration Policy Update', 
    'Please note that each student can only register for one room at a time. If you wish to change your room, please cancel your existing registration first or contact the hostel administration.', 
    1
);
