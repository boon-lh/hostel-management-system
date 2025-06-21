<?php
/**
 * Direct Status Update Tool
 * Updates a complaint status directly in the database
 */
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Status Update Tool - MMU Hostel Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1000px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .table {
            background-color: #fff;
        }
        .status-select {
            min-width: 140px;
        }
        .alert {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .btn-back {
            margin-top: 20px;
        }
        .highlight-row {
            background-color: #e8f5e9 !important;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff8e1;
            color: #f57c00;
        }
        .status-in_progress {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .status-resolved {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .status-closed {
            background-color: #eeeeee;
            color: #616161;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tools me-2"></i>Direct Status Update Tool</h1>
            <a href="complaints.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Complaints
            </a>
        </div>

<?php

// Check if there's an update request
if (isset($_POST['complaint_id']) && isset($_POST['new_status'])) {
    $complaintId = intval($_POST['complaint_id']);
    $newStatus = $_POST['new_status'];
    
    // Connect to the database
    $conn = mysqli_connect("localhost", "root", "", "hostel_management");
    if (!$conn) {
        echo "<div style='color: red;'>Failed to connect to database: " . mysqli_connect_error() . "</div>";
        exit();
    }
    
    // Check if the complaint exists
    $checkResult = mysqli_query($conn, "SELECT id, status FROM complaints WHERE id = $complaintId");
    if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
        echo "<div style='color: red;'>Complaint ID $complaintId not found</div>";
        exit();
    }
    
    $currentData = mysqli_fetch_assoc($checkResult);
    $currentStatus = $currentData['status'];
    
    echo "<div>Current status for complaint #$complaintId: <strong>$currentStatus</strong></div>";
    
    // Try first with prepared statement for safety
    $stmt = mysqli_prepare($conn, "UPDATE complaints SET status = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $newStatus, $complaintId);
        $preparedSuccess = mysqli_stmt_execute($stmt);
        echo "<div>Prepared statement execution: " . ($preparedSuccess ? "Successful" : "Failed - " . mysqli_stmt_error($stmt)) . "</div>";
        echo "<div>Affected rows from prepared statement: " . mysqli_stmt_affected_rows($stmt) . "</div>";
    } else {
        echo "<div style='color: orange;'>Could not prepare statement: " . mysqli_error($conn) . "</div>";
    }
    
    // For reliability, also try direct query
    $updateResult = mysqli_query($conn, "UPDATE complaints SET status = '$newStatus' WHERE id = $complaintId");
    if (!$updateResult) {
        echo "<div style='color: red;'>Direct update failed: " . mysqli_error($conn) . "</div>";
    } else {
        echo "<div>Direct query execution: Successful</div>";
        echo "<div>Affected rows from direct query: " . mysqli_affected_rows($conn) . "</div>";
    }
    
    // Check if the update was successful
    $verifyResult = mysqli_query($conn, "SELECT id, status FROM complaints WHERE id = $complaintId");
    $verifiedData = mysqli_fetch_assoc($verifyResult);
    
    if ($verifiedData['status'] == $newStatus) {
        echo "<div style='color: green; font-size: 18px; margin: 10px 0; padding: 10px; background: #e8f5e9; border-radius: 5px;'>
            Status successfully updated to: <strong>" . $verifiedData['status'] . "</strong>
        </div>";
    } else {
        echo "<div style='color: red; font-size: 18px; margin: 10px 0; padding: 10px; background: #ffebee; border-radius: 5px;'>
            Status update failed. Current status: " . $verifiedData['status'] . "
        </div>";
    }
    
    // Also check complaint structure
    echo "<h3>Complaint Record Details</h3>";
    $detailResult = mysqli_query($conn, "SELECT * FROM complaints WHERE id = $complaintId");
    if ($detailResult && $complaint = mysqli_fetch_assoc($detailResult)) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        foreach ($complaint as $field => $value) {
            echo "<tr><th>$field</th><td>" . ($value === NULL ? "NULL" : $value) . "</td></tr>";
        }
        echo "</table>";
    }
}

// Show a list of complaints
echo "<h2>Select a Complaint to Update</h2>";

$conn = mysqli_connect("localhost", "root", "", "hostel_management");
if (!$conn) {
    echo "<div style='color: red;'>Failed to connect to database: " . mysqli_connect_error() . "</div>";
    exit();
}

// Check if a specific complaint ID is passed in the URL
$whereClause = "";
$specificComplaintId = isset($_GET['complaint_id']) ? intval($_GET['complaint_id']) : 0;
if ($specificComplaintId > 0) {
    $whereClause = "WHERE c.id = $specificComplaintId";
}

$result = mysqli_query($conn, "SELECT c.id, c.subject, c.status, s.name as student_name 
                              FROM complaints c
                              JOIN students s ON c.student_id = s.id
                              $whereClause
                              ORDER BY c.id DESC LIMIT 20");

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div>No complaints found.</div>";
    exit();
}

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Subject</th><th>Student</th><th>Current Status</th><th>Actions</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr id='complaint-{$row['id']}'>";
    echo "<td><strong>{$row['id']}</strong></td>";
    echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
    
    // Show status with proper formatting
    $statusClass = 'status-' . $row['status'];
    echo "<td><span class='status-badge {$statusClass}'>" . ucwords(str_replace('_', ' ', $row['status'])) . "</span></td>";
    
    echo "<td>";
    echo "<form method='post' class='d-flex align-items-center'>";
    echo "<input type='hidden' name='complaint_id' value='{$row['id']}'>";
    echo "<select name='new_status' class='form-select status-select me-2'>";
    echo "<option value='pending'" . ($row['status'] == 'pending' ? " selected" : "") . ">Pending</option>";
    echo "<option value='in_progress'" . ($row['status'] == 'in_progress' ? " selected" : "") . ">In Progress</option>";
    echo "<option value='resolved'" . ($row['status'] == 'resolved' ? " selected" : "") . ">Resolved</option>";
    echo "<option value='closed'" . ($row['status'] == 'closed' ? " selected" : "") . ">Closed</option>";
    echo "</select>";
    echo "<button type='submit' class='btn btn-primary btn-sm'><i class='fas fa-save me-1'></i>Update</button>";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
?>

    </div>
    
    <script>
        // Highlight the specific complaint row if URL parameter is set
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const complaintId = urlParams.get('complaint_id');
            
            if (complaintId) {
                const row = document.querySelector(`input[name="complaint_id"][value="${complaintId}"]`).closest('tr');
                if (row) {
                    row.classList.add('highlight-row');
                    // Scroll to the highlighted row
                    row.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            }
        });
    </script>
</body>
</html>
