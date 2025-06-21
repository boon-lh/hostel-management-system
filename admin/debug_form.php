<?php
/**
 * Debug Form Submissions and Inspect Database Status
 */
header('Content-Type: text/html');
echo "<h1>Debug Form Submissions</h1>";

// Simple test form with same fields as complaint status update form
echo "
<h2>Test Form</h2>
<form method='post' action='complaint_ajax_handler.php'>
    <input type='hidden' name='action' value='update_status'>
    <p>
        <label>Complaint ID:</label>
        <input type='number' name='complaint_id' required>
    </p>
    <p>
        <label>New Status:</label>
        <select name='new_status' required>
            <option value='pending'>Pending</option>
            <option value='in_progress'>In Progress</option>
            <option value='resolved'>Resolved</option>
            <option value='closed'>Closed</option>
        </select>
    </p>
    <p>
        <label>Comments:</label>
        <textarea name='comments'></textarea>
    </p>
    <button type='submit'>Test Update</button>
</form>
";

// Database connection status check
echo "<h2>Database Connection Test</h2>";
$conn = mysqli_connect("localhost", "root", "", "hostel_management");
if (!$conn) {
    echo "<p style='color: red;'>Connection failed: " . mysqli_connect_error() . "</p>";
} else {
    echo "<p style='color: green;'>Database connection successful!</p>";

    // Show complaint status list
    $result = mysqli_query($conn, "SELECT id, status FROM complaints ORDER BY id DESC LIMIT 10");
    if ($result) {
        echo "<h3>Recent Complaints Status</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Status</th><th>Test Direct Update</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>";
            // Add direct update buttons
            foreach (['pending', 'in_progress', 'resolved', 'closed'] as $status) {
                if ($status != $row['status']) {
                    echo "<form method='post' action='direct_status_update.php' style='display:inline;'>";
                    echo "<input type='hidden' name='complaint_id' value='{$row['id']}'>";
                    echo "<input type='hidden' name='new_status' value='$status'>";
                    echo "<button type='submit' style='margin-right:5px;'>$status</button>";
                    echo "</form>";
                }
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Error querying complaints: " . mysqli_error($conn) . "</p>";
    }
    
    // Database structure check
    echo "<h3>Complaints Table Structure</h3>";
    $structure = mysqli_query($conn, "DESCRIBE complaints");
    if ($structure) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($field = mysqli_fetch_assoc($structure)) {
            echo "<tr>";
            foreach ($field as $key => $value) {
                echo "<td>" . ($value === NULL ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if the status field is an enum and what values it supports
        $result = mysqli_query($conn, "SHOW COLUMNS FROM complaints WHERE Field = 'status'");
        if ($result && $field = mysqli_fetch_assoc($result)) {
            if (preg_match("/^enum\((.+)\)$/", $field['Type'], $matches)) {
                echo "<p>Status field is an enum with values: {$matches[1]}</p>";
            }
        }
    } else {
        echo "<p>Error checking table structure: " . mysqli_error($conn) . "</p>";
    }

    mysqli_close($conn);
}

// Add links to related pages
echo "<div style='margin-top: 20px;'>";
echo "<p><a href='complaints.php'>Back to Complaints</a> | ";
echo "<a href='direct_status_update.php'>Direct Status Update Tool</a></p>";
echo "</div>";
?>
