<?php
session_start();
// Include database connection
include_once '../shared/includes/db_connection.php';
// Include header
include_once '../shared/includes/header.php';
// Include student sidebar
include_once '../shared/includes/sidebar-student.php';

// Check if student is logged in, otherwise redirect to login page
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

// Fetch student details
$student_id = $_SESSION['user_id'];
// $stmt_student = $conn->prepare("SELECT * FROM students WHERE id = ?");
// $stmt_student->bind_param("i", $student_id);
// $stmt_student->execute();
// $result_student = $stmt_student->get_result();
// $student = $result_student->fetch_assoc();
// $stmt_student->close();

// --- Hostel Registration Logic ---
// Fetch available rooms and their features from database
$rooms = [];

// Check if the database tables exist
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'rooms'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    // Fetch rooms and their blocks from the database
    $sql = "SELECT r.*, b.block_name FROM rooms r 
            JOIN hostel_blocks b ON r.block_id = b.id
            ORDER BY b.block_name, r.room_number";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rooms[] = [
                "id" => $row["id"],
                "block" => $row["block_name"],
                "room_number" => $row["room_number"],
                "type" => $row["type"],
                "price" => $row["price"] . " MYR",
                "features" => $row["features"],
                "availability" => $row["availability_status"]
            ];
        }
    }
} else {
    // Use placeholder data if the database tables don't exist yet
    $rooms = [
        ["id" => 1, "block" => "A", "room_number" => "101", "type" => "Single", "price" => "500 MYR", "features" => "Attached bathroom, Wi-Fi, Study Table, Wardrobe", "availability" => "Available"],
        ["id" => 2, "block" => "A", "room_number" => "102", "type" => "Double", "price" => "350 MYR", "features" => "Shared bathroom, Wi-Fi, Study Table, Wardrobe", "availability" => "Available"],
        ["id" => 3, "block" => "B", "room_number" => "201", "type" => "Single", "price" => "550 MYR", "features" => "Attached bathroom, Air-Conditioning, Wi-Fi, Study Table, Wardrobe", "availability" => "Unavailable"],
        ["id" => 4, "block" => "C", "room_number" => "301", "type" => "Double", "price" => "400 MYR", "features" => "Shared bathroom, Wi-Fi, Study Table, Wardrobe", "availability" => "Available"],
    ];
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_room'])) {
    $selected_room_id = $_POST['room_id'];
    
    // Check if the database tables exist
    $registration_table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'hostel_registrations'");
    if ($result && $result->num_rows > 0) {
        $registration_table_exists = true;
    }

    if ($registration_table_exists) {
        // 1. Check if the room is still available
        $stmt_check_room = $conn->prepare("SELECT availability_status FROM rooms WHERE id = ?");
        $stmt_check_room->bind_param("i", $selected_room_id);
        $stmt_check_room->execute();
        $result_room = $stmt_check_room->get_result();
        $room_data = $result_room->fetch_assoc();
        $stmt_check_room->close();
        
        if ($room_data && $room_data['availability_status'] === 'Available') {
            // 2. Check if student already has an active registration
            $stmt_check_existing = $conn->prepare("SELECT * FROM hostel_registrations WHERE student_id = ? AND status IN ('Pending', 'Approved', 'Checked In')");
            $stmt_check_existing->bind_param("i", $student_id);
            $stmt_check_existing->execute();
            $result_existing = $stmt_check_existing->get_result();
            $stmt_check_existing->close();
            
            if ($result_existing->num_rows > 0) {
                // Student already has an active registration
                $message = "You already have an active hostel registration. Please check its status before submitting a new one.";
                $message_type = "warning";
            } else {
                // 3. All is good, proceed with registration
                $registration_date = date('Y-m-d H:i:s');
                $status = 'Pending';
                $requested_check_in = date('Y-m-d', strtotime('+7 days')); // Default check-in date a week from now
                
                $stmt_insert_registration = $conn->prepare("INSERT INTO hostel_registrations (student_id, room_id, registration_date, requested_check_in_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert_registration->bind_param("iisss", $student_id, $selected_room_id, $registration_date, $requested_check_in, $status);
                
                if ($stmt_insert_registration->execute()) {
                    // 4. Update room availability status
                    $stmt_update_room = $conn->prepare("UPDATE rooms SET availability_status = 'Pending Confirmation' WHERE id = ?");
                    $stmt_update_room->bind_param("i", $selected_room_id);
                    $stmt_update_room->execute();
                    $stmt_update_room->close();
                    
                    $message = "Registration request submitted successfully. You will be notified once approved.";
                    $message_type = "success";
                } else {
                    $message = "Error submitting registration: " . $conn->error;
                    $message_type = "danger";
                }
                
                $stmt_insert_registration->close();
            }
        } else {
            $message = "This room is no longer available. Please select another room.";
            $message_type = "warning";
        }
    } else {
        // Database tables don't exist yet
        $message = "Registration request for room ID " . htmlspecialchars($selected_room_id) . " submitted. You will be notified once confirmed.";
        $message_type = "info";
    }
}

?>

<link rel="stylesheet" href="css/hostel_registration.css"> <!-- We will create this CSS file next -->

<div class="main-content">
    <div class="container">
        <h2>Hostel Registration - Room Features & Availability</h2>
        <p>Browse available rooms and select one to register. Please note that registration is subject to approval.</p>        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo isset($message_type) ? $message_type : 'info'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="room-list">
            <?php if (!empty($rooms)): ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card <?php echo ($room['availability'] !== 'Available') ? 'unavailable' : ''; ?>">
                        <h3>Room <?php echo htmlspecialchars($room['block'] . '-' . $room['room_number']); ?></h3>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($room['type']); ?></p>
                        <p><strong>Price:</strong> <?php echo htmlspecialchars($room['price']); ?> / month</p>
                        <p><strong>Features:</strong> <?php echo htmlspecialchars($room['features']); ?></p>
                        <p><strong>Status:</strong> <span class="status-<?php echo strtolower(str_replace(' ', '-', $room['availability'])); ?>"><?php echo htmlspecialchars($room['availability']); ?></span></p>
                        
                        <?php if ($room['availability'] === 'Available'): ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                <button type="submit" name="register_room" class="btn btn-primary">Register for this Room</button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" disabled>Unavailable</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No rooms are currently listed. Please check back later or contact administration.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../shared/includes/footer.php';
?>
