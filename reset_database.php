<?php
echo "<h2>Database Reset Tool</h2>";
echo "<p>This tool will:</p>";
echo "<ul>";
echo "<li>1. Fix phpMyAdmin configuration storage</li>";
echo "<li>2. Drop the existing hostel_management database</li>";
echo "<li>3. Create a new hostel_management database</li>";
echo "<li>4. Import the updated database structure</li>";
echo "</ul>";

// Connect to MySQL server (not specific database)
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty

try {
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h3>Step 1: Fixing phpMyAdmin Configuration Storage</h3>";
    
    // Fix phpMyAdmin configuration
    $phpMyAdminSQL = file_get_contents('fix_phpmyadmin.sql');
    if ($conn->multi_query($phpMyAdminSQL)) {
        echo "✓ phpMyAdmin configuration tables created successfully.<br>";
        // Consume all results
        while ($conn->next_result()) {;}
    } else {
        echo "⚠ Warning: Could not create phpMyAdmin tables: " . $conn->error . "<br>";
    }
    
    echo "<h3>Step 2: Resetting hostel_management Database</h3>";
    
    // Disable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    echo "✓ Foreign key checks disabled.<br>";
      // Try to drop database, if fails, drop tables individually
    $dropResult = $conn->query('DROP DATABASE IF EXISTS hostel_management');
    if ($dropResult) {
        echo "✓ Old database dropped successfully.<br>";
        
        // Create new database
        if ($conn->query('CREATE DATABASE hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci')) {
            echo "✓ New database created successfully.<br>";
        } else {
            die("❌ Error creating database: " . $conn->error);
        }
    } else {
        echo "⚠ DROP DATABASE disabled. Using alternative method...<br>";
        
        // Select existing database and drop all tables
        if ($conn->select_db('hostel_management')) {
            echo "✓ Selected existing database.<br>";
            
            // Drop all tables individually
            $tables = [
                'complaint_status_history',
                'maintenance_assignments', 
                'request_status_history',
                'hostel_registrations',
                'emergency_contacts',
                'invoices',
                'payments',
                'bills',
                'refunds',
                'service_requests',
                'complaints',
                'rooms',
                'hostel_blocks',
                'maintenance_staff',
                'announcements',
                'visitors',
                'students',
                'admins'
            ];
            
            foreach ($tables as $table) {
                if ($conn->query("DROP TABLE IF EXISTS `$table`")) {
                    echo "✓ Dropped table: $table<br>";
                } else {
                    echo "⚠ Could not drop table $table: " . $conn->error . "<br>";
                }
            }
            echo "✓ All tables dropped.<br>";
        } else {
            // Database doesn't exist, create it
            if ($conn->query('CREATE DATABASE hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci')) {
                echo "✓ New database created successfully.<br>";
            } else {
                die("❌ Error creating database: " . $conn->error);
            }
        }
    }
    
    // Select the database
    if ($conn->select_db('hostel_management')) {
        echo "✓ Database selected.<br>";
    } else {
        die("❌ Error selecting database: " . $conn->error);
    }
    
    echo "<h3>Step 3: Importing Database Structure</h3>";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database_setup.sql');
    if (!$sql) {
        die("❌ Error: Could not read database_setup.sql file");
    }
    
    if ($conn->multi_query($sql)) {
        echo "✓ Database structure import started.<br>";
        
        // Process all results
        $queryCount = 0;
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
            $queryCount++;
        } while ($conn->next_result());
        
        echo "✓ Processed $queryCount SQL statements.<br>";
        
        if ($conn->error) {
            echo "⚠ Last error: " . $conn->error . "<br>";
        } else {
            echo "✓ Database import completed successfully!<br>";
        }
    } else {
        echo "❌ Error importing SQL: " . $conn->error . "<br>";
    }
    
    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    echo "✓ Foreign key checks re-enabled.<br>";
    
    echo "<h3>Step 4: Verification</h3>";
    
    // Verify tables were created
    $result = $conn->query("SHOW TABLES");
    if ($result && $result->num_rows > 0) {
        echo "✓ Database contains " . $result->num_rows . " tables:<br>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "⚠ Warning: No tables found in database.<br>";
    }
    
    $conn->close();
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h4>✅ Process Complete!</h4>";
    echo "<p>Your database has been successfully reset. You can now:</p>";
    echo "<ul>";
    echo "<li>Access phpMyAdmin without the configuration storage warning</li>";
    echo "<li>Use your hostel management system with the updated database structure</li>";
    echo "<li><a href='index.php'>Return to homepage</a></li>";
    echo "<li><a href='admin/login.php'>Go to Admin Login</a></li>";
    echo "<li><a href='student/login.php'>Go to Student Login</a></li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
