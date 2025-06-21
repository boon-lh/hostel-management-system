<?php
/**
 * Database Debug Tool
 * Tests database connection and query execution
 */
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        textarea { width: 100%; height: 120px; font-family: monospace; padding: 10px; }
        .code { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        pre { margin: 0; }
    </style>
</head>
<body>
    <h1>Database Debug Tool</h1>

<?php
// Display PHP info
echo "<div class='code'><pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "</pre></div>";

// Connect to the database
$conn = mysqli_connect("localhost", "root", "", "hostel_management");
if (!$conn) {
    echo "<div class='error'>Failed to connect to database: " . mysqli_connect_error() . "</div>";
    exit();
}

echo "<div class='success'>Successfully connected to database.</div>";

// Check if the complaints table exists
echo "<h2>Complaints Table Check</h2>";
$tableResult = mysqli_query($conn, "SHOW TABLES LIKE 'complaints'");
if (!$tableResult || mysqli_num_rows($tableResult) == 0) {
    echo "<div class='error'>ERROR: complaints table does not exist!</div>";
    exit();
}
echo "<div class='success'>Complaints table exists.</div>";

// Get the structure of the complaints table
echo "<h2>Complaints Table Structure</h2>";
$structureResult = mysqli_query($conn, "DESCRIBE complaints");
if (!$structureResult) {
    echo "<div class='error'>Could not get table structure: " . mysqli_error($conn) . "</div>";
    exit();
}

echo "<table>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($structureResult)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] === NULL ? "NULL" : $row['Default']) . "</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check if we have status enum values
echo "<h2>Status Field Check</h2>";
$enumResult = mysqli_query($conn, "SHOW COLUMNS FROM complaints WHERE Field = 'status'");
if (!$enumResult) {
    echo "<div class='error'>Could not get status field: " . mysqli_error($conn) . "</div>";
    exit();
}

$enumRow = mysqli_fetch_assoc($enumResult);
echo "<div class='code'><pre>Status field type: " . $enumRow['Type'] . "</pre></div>";

if (preg_match("/^enum\((.+)\)$/", $enumRow['Type'], $matches)) {
    echo "<div class='success'>Status field is an enum with values: {$matches[1]}</div>";
}

// Test updating a complaint status
echo "<h2>Update Test</h2>";

// Get a complaint ID to test with
$idResult = mysqli_query($conn, "SELECT id, status FROM complaints ORDER BY id DESC LIMIT 1");
if (!$idResult || mysqli_num_rows($idResult) == 0) {
    echo "<div class='error'>No complaints found to test with.</div>";
} else {
    $complaint = mysqli_fetch_assoc($idResult);
    $complaintId = $complaint['id'];
    $currentStatus = $complaint['status'];

    echo "<div>Found complaint ID: $complaintId with current status: <strong>$currentStatus</strong></div>";

    // Try to update the status
    $newStatus = ($currentStatus == 'pending') ? 'in_progress' : 'pending';
    echo "<div>Attempting to update to: <strong>$newStatus</strong></div>";

    // Try with prepared statement first
    $stmt = mysqli_prepare($conn, "UPDATE complaints SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $complaintId);
    $preparedSuccess = mysqli_stmt_execute($stmt);
    
    echo "<div>" . ($preparedSuccess ? 
        "<span class='success'>Prepared statement update succeeded</span>" : 
        "<span class='error'>Prepared statement failed: " . mysqli_stmt_error($stmt) . "</span>") . 
        " (Affected rows: " . mysqli_stmt_affected_rows($stmt) . ")</div>";
    
    // Also try direct query for comparison
    $updateResult = mysqli_query($conn, "UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId");
if (!$updateResult) {
        echo "<div class='error'>Direct query update failed: " . mysqli_error($conn) . "</div>";
    } else {
        echo "<div class='success'>Direct query update succeeded (Affected rows: " . mysqli_affected_rows($conn) . ")</div>";
    }
    
    // Verify the update
    $verifyResult = mysqli_query($conn, "SELECT id, status FROM complaints WHERE id = $complaintId");
    if (!$verifyResult) {
        echo "<div class='error'>Verification query failed: " . mysqli_error($conn) . "</div>";
    } else {
        $verifiedData = mysqli_fetch_assoc($verifyResult);
        if ($verifiedData['status'] === $newStatus) {
            echo "<div class='success'>Status successfully updated and verified: " . $verifiedData['status'] . "</div>";
        } else {
            echo "<div class='error'>Status update failed or didn't take effect. Current status: " . $verifiedData['status'] . "</div>";
        }
    }
    
    // Form to test updates
    echo "<h2>Update Status Form</h2>";
    echo "<form method='post' action='direct_status_update.php'>";
    echo "<input type='hidden' name='complaint_id' value='$complaintId'>";
    echo "<div>Complaint ID: $complaintId</div>";
    echo "<div style='margin:10px 0;'>New Status: ";
    echo "<select name='new_status'>";
    echo "<option value='pending'" . ($currentStatus == 'pending' ? " selected" : "") . ">Pending</option>";
    echo "<option value='in_progress'" . ($currentStatus == 'in_progress' ? " selected" : "") . ">In Progress</option>";
    echo "<option value='resolved'" . ($currentStatus == 'resolved' ? " selected" : "") . ">Resolved</option>";
    echo "<option value='closed'" . ($currentStatus == 'closed' ? " selected" : "") . ">Closed</option>";
    echo "</select></div>";
    echo "<button type='submit'>Update Status</button>";
    echo "</form>";
}

// Show all complaints
echo "<h2>All Complaints</h2>";

$allResult = mysqli_query($conn, "SELECT c.id, c.subject, c.status, c.priority, s.name as student_name 
                                FROM complaints c
                                JOIN students s ON c.student_id = s.id
                                ORDER BY c.id DESC LIMIT 10");

if (!$allResult) {
    echo "<div class='error'>Could not fetch complaints: " . mysqli_error($conn) . "</div>";
} else if (mysqli_num_rows($allResult) == 0) {
    echo "<div>No complaints found.</div>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Subject</th><th>Student</th><th>Priority</th><th>Status</th><th>Actions</th></tr>";
    
    while ($row = mysqli_fetch_assoc($allResult)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['subject']}</td>";
        echo "<td>{$row['student_name']}</td>";
        echo "<td>{$row['priority']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>";
        echo "<form method='post' action='direct_status_update.php' style='display:inline;'>";
        echo "<input type='hidden' name='complaint_id' value='{$row['id']}'>";
        echo "<select name='new_status' onchange='this.form.submit()'>";
        echo "<option value=''>Update Status...</option>";
        echo "<option value='pending'>Pending</option>";
        echo "<option value='in_progress'>In Progress</option>";
        echo "<option value='resolved'>Resolved</option>";
        echo "<option value='closed'>Closed</option>";
        echo "</select>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<div style='margin-top:20px;'>";
echo "<a href='complaints.php'>Back to Complaints</a> | ";
echo "<a href='direct_status_update.php'>Direct Status Update Tool</a>";
echo "</div>";

mysqli_close($conn);
?>
</body>
</html>
    echo "ERROR: Update failed: " . mysqli_error($conn) . "\n";
    exit();
}

echo "Update query executed. Affected rows: " . mysqli_affected_rows($conn) . "\n";

// Verify the update
$verifyResult = mysqli_query($conn, "SELECT id, status FROM complaints WHERE id = $complaintId");
if (!$verifyResult) {
    echo "ERROR: Could not verify update: " . mysqli_error($conn) . "\n";
    exit();
}

$verifiedData = mysqli_fetch_assoc($verifyResult);
echo "After update - Status: " . $verifiedData['status'] . "\n";

if ($verifiedData['status'] == $newStatus) {
    echo "SUCCESS: Status was updated correctly!\n";
} else {
    echo "ERROR: Status was NOT updated. Before: $currentStatus, After: {$verifiedData['status']}, Expected: $newStatus\n";
}

echo "\n=================\n";
echo "Debug complete.\n";
?>
