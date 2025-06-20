<?php
require_once 'shared/includes/db_connection.php';

// Get complaint with ID = 3
$stmt = $conn->prepare("SELECT c.*, sh.* 
                      FROM complaints c 
                      LEFT JOIN complaint_status_history sh ON c.id = sh.complaint_id 
                      WHERE c.id = 3");
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Complaint #3 Details</h2>";

if ($result->num_rows > 0) {
    echo "<h3>Complaint Information</h3>";
    echo "<pre>";
    $row = $result->fetch_assoc();
    print_r($row);
    echo "</pre>";
    
    // Check status history
    $stmt = $conn->prepare("SELECT * FROM complaint_status_history WHERE complaint_id = 3");
    $stmt->execute();
    $history = $stmt->get_result();
    
    echo "<h3>Status History Records</h3>";
    echo "<pre>";
    if ($history->num_rows > 0) {
        while ($historyRow = $history->fetch_assoc()) {
            print_r($historyRow);
        }
    } else {
        echo "No history records found";
    }
    echo "</pre>";
} else {
    echo "No complaint found with ID #3";
}

// Check for foreign key constraints
$stmt = $conn->prepare("SHOW CREATE TABLE complaint_status_history");
$stmt->execute();
$tableResult = $stmt->get_result();
$tableDefinition = $tableResult->fetch_assoc();

echo "<h3>Table Definition for complaint_status_history</h3>";
echo "<pre>";
print_r($tableDefinition);
echo "</pre>";
?>
