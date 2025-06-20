<?php
// Direct update script for testing
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    echo "Access denied. Admin login required.";
    exit();
}

require_once "../shared/includes/db_connection.php";

// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $room_id = isset($_POST["room_id"]) ? intval($_POST["room_id"]) : 0;
    $new_status = isset($_POST["new_status"]) ? $_POST["new_status"] : "";
    
    if (empty($room_id) || empty($new_status)) {
        echo "<p>Error: Missing required fields.</p>";
        echo "<pre>POST data: " . print_r($_POST, true) . "</pre>";
        exit();
    }
    
    // Validate status
    $valid_statuses = ["Available", "Occupied", "Under Maintenance"];
    if (!in_array($new_status, $valid_statuses)) {
        echo "<p>Error: Invalid status value.</p>";
        exit();
    }
    
    // Update database
    $sql = "UPDATE rooms SET availability_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $room_id);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        if ($affected_rows > 0) {
            echo "<p>Success! Room status updated successfully.</p>";
            echo "<p>Room ID: $room_id, New Status: $new_status, Rows affected: $affected_rows</p>";
            
            // Verify the update
            $verify_sql = "SELECT id, room_number, availability_status FROM rooms WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $room_id);
            $verify_stmt->execute();
            $result = $verify_stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo "<p>Verification: Room " . $row["room_number"] . " now has status: " . $row["availability_status"] . "</p>";
            }
        } else {
            echo "<p>Error: No rows were affected. The room ID may not exist or the status is unchanged.</p>";
            
            // Check if room exists
            $check_sql = "SELECT id, room_number, availability_status FROM rooms WHERE id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $room_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($row = $check_result->fetch_assoc()) {
                echo "<p>Room exists: Room " . $row["room_number"] . " has current status: " . $row["availability_status"] . "</p>";
                echo "<p>Note: If the current status and new status are the same, no rows will be affected.</p>";
            } else {
                echo "<p>Room with ID $room_id does not exist.</p>";
            }
        }
    } else {
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    
    echo "<p><a href='block_rooms.php'>Return to rooms list</a></p>";
    
} else {
    // Display form for direct testing
    $rooms_query = "SELECT id, room_number, availability_status FROM rooms ORDER BY room_number";
    $rooms_result = $conn->query($rooms_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Room Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin-bottom: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; margin-bottom: 5px; }
        select { margin-bottom: 15px; padding: 5px; width: 200px; }
        button { padding: 8px 15px; background: #4776e6; color: white; border: none; border-radius: 4px; cursor: pointer; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Direct Room Status Update</h1>
    <p>Use this form to directly update a room's status in the database:</p>
    
    <form method="post" action="">
        <label for="room_id">Select Room:</label>
        <select name="room_id" id="room_id">
            <?php
            if ($rooms_result->num_rows > 0) {
                while ($room = $rooms_result->fetch_assoc()) {
                    echo "<option value=\"" . $room['id'] . "\">" . $room['room_number'] . " (Current: " . $room['availability_status'] . ")</option>";
                }
            }
            ?>
        </select>
        
        <label for="new_status">New Status:</label>
        <select name="new_status" id="new_status">
            <option value="Available">Available</option>
            <option value="Occupied">Occupied</option>
            <option value="Under Maintenance">Under Maintenance</option>
        </select>
        
        <button type="submit">Update Status</button>
    </form>
    
    <a href="block_rooms.php">Return to rooms list</a>
</body>
</html>
<?php
}
$conn->close();
?>
