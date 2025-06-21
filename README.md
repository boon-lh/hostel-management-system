# MMU Hostel Management System

## Prerequisites

Before installing this system, make sure you have:
- XAMPP installed (version 7.4 or higher recommended)
- Web browser (Google Chrome, Firefox, or Edge)

## Setup Instructions

### Step 1: Install XAMPP
If you don't have XAMPP installed:
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP with default settings
3. During installation, make sure Apache and MySQL components are selected

### Step 2: Extract the Project Files
1. Extract the zip file containing the hostel management system
2. Copy the entire "hostel-management-system" folder to your XAMPP htdocs directory:
   - Windows: `C:\xampp\htdocs\`
   - Mac: `/Applications/XAMPP/htdocs/`
   - Linux: `/opt/lampp/htdocs/`

### Step 3: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start Apache and MySQL services by clicking the "Start" buttons next to them
3. Ensure both services show running status (green)

### Step 4: Set Up the Database
1. Open your web browser and navigate to: http://localhost/phpmyadmin
2. Create a new database:
   - Click "New" on the left sidebar
   - Database name: `hostel_management`
   - Character set: utf8mb4_general_ci
   - Click "Create"
3. Select the new `hostel_management` database
4. Import the database:
   - Click on the "Import" tab
   - Click "Choose File" and select the `database_setup.sql` file from the project folder
   - Click "Go" at the bottom to import the database structure

### Step 5: Import Additional Data
For demo data, follow these additional steps:
1. In phpMyAdmin, make sure the `hostel_management` database is selected
2. Go to "Import" tab
3. Import the following files in this order:
   - `insert_hostel_data.sql` - Adds blocks and rooms
   - `add_visitor_purpose_column.sql` - Updates schema with visitor purposes
   - `update_room_constraints.sql` - Updates room constraints

### Step 6: Access the System
1. Open your web browser
2. Navigate to: http://localhost/hostel-management-system

### Step 7: Login 

## System Features

- Student Management
- Room Management
- Hostel Registration
- Complaints System
- Financial Management
- Visitor Management
- Announcements
- User Profile Management

## Troubleshooting

### Database Connection Issues
If you encounter database connection errors, check:
1. XAMPP services are running
2. Database name is correctly set up as `hostel_management`
3. Database connection parameters in `shared/includes/db_connection.php` match your MySQL setup

### File Permission Issues
If you encounter issues with uploading files or profile pictures:
1. Ensure the `uploads` directory has write permissions
2. On Linux/Mac systems, you might need to run: `chmod -R 755 hostel-management-system`

### Page Not Found Errors
If you get 404 errors:
1. Verify that the system is installed in `xampp/htdocs/hostel-management-system`
2. Check that Apache is running
3. Try accessing http://localhost to confirm Apache is working properly

## Support
For any additional help or questions, please contact [Your Contact Information].

---
© 2025 MMU Hostel Management System | All Rights Reserved
