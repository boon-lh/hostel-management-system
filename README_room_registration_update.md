# Room Registration Constraint Update

## Changes Made

1. Removed "Register Another Room" button from the student's registration history page
2. Added validation to prevent students from registering for more than one room at a time
3. Created SQL file to add database constraints to enforce the one-room-per-student policy
4. Added user-friendly error messages when a student attempts to register for multiple rooms

## How to Apply the Database Constraint

To enforce the one-room-per-student policy at the database level, please execute the SQL script:

1. Log in to your MySQL/MariaDB server via phpMyAdmin
2. Select the `hostel_management` database
3. Go to the "SQL" tab
4. Copy and paste the contents of `update_room_constraints.sql` into the SQL window
5. Click "Execute"

Alternatively, you can run the script directly from the command line:

```
mysql -u your_username -p hostel_management < update_room_constraints.sql
```

## Technical Details

The update:
1. Adds a unique index on the `hostel_registrations` table that prevents a student from having multiple registrations in active states ('Pending', 'Approved', or 'Checked In')
2. Updates any existing registrations in non-active statuses to 'Expired' to avoid conflicts
3. Adds a system announcement to inform users of the policy change
4. Implements application-level validation in the PHP code to prevent multiple registration attempts

## Testing

After applying the changes:
1. Log in as a student
2. Try to register for a room
3. Once registered, try accessing the room registration page again
4. You should be redirected to your current registrations with a warning message

If a student needs to register for a different room, they must first cancel their existing registration (if in 'Pending' status) or contact the administration to handle other cases.
