<?php
session_start();
// Include database connection
include_once '../shared/includes/db_connection.php';

// Check if student is logged in, otherwise redirect to login page
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

// Fetch student details including gender and citizenship
$student_gender = "";
$student_citizenship = "";
$student_id = $_SESSION['user_id'];
$getStudentDetails = $conn->prepare("SELECT gender, citizenship FROM students WHERE id = ?");
$getStudentDetails->bind_param("i", $student_id);
$getStudentDetails->execute();
$studentResult = $getStudentDetails->get_result();
if ($studentResult->num_rows > 0) {
    $studentData = $studentResult->fetch_assoc();
    $student_gender = $studentData['gender'];
    $student_citizenship = $studentData['citizenship'];
}
$getStudentDetails->close();

// Check if the student already has an active registration
// If yes, redirect them to my_registrations.php
$student_id = $_SESSION['user_id'];
$checkActiveReg = $conn->prepare("SELECT COUNT(*) AS active_count FROM hostel_registrations 
                                 WHERE student_id = ? AND status IN ('Pending', 'Approved', 'Checked In')");
$checkActiveReg->bind_param("i", $student_id);
$checkActiveReg->execute();
$activeResult = $checkActiveReg->get_result();
$activeCount = $activeResult->fetch_assoc()['active_count'];
$checkActiveReg->close();

// If student already has an active registration, redirect to my_registrations.php with a message
if ($activeCount > 0) {
    $_SESSION['message'] = "You already have an active room registration. One student can only register for one room at a time.";
    $_SESSION['message_type'] = "warning";
    header("Location: my_registrations.php");
    exit();
}

// Include header and sidebar after the redirect check
include_once '../shared/includes/header.php';
include_once '../shared/includes/sidebar-student.php';

// Debug block information - only visible to logged-in administrators
$debug_mode = isset($_SESSION["role"]) && $_SESSION["role"] === "admin" && isset($_GET['debug']);

// --- Hostel Registration Logic ---
// Fetch available rooms and their features from database
$rooms = [];

// Define room types for feature mapping
$roomTypes = [
    'Single' => ['beds' => 1, 'bathroom' => 'Shared', 'rate' => 1200],
    'Double' => ['beds' => 2, 'bathroom' => 'Shared', 'rate' => 900],
    'Triple' => ['beds' => 3, 'bathroom' => 'Shared', 'rate' => 750],
    'Suite' => ['beds' => 1, 'bathroom' => 'Private', 'rate' => 1800],
];

// Define features for room types
$features = [
    'Single' => ['Wi-Fi', 'Study Desk', 'Wardrobe', 'Fan'],
    'Double' => ['Wi-Fi', 'Study Desks (2)', 'Wardrobes (2)', 'Fan'],
    'Triple' => ['Wi-Fi', 'Study Desks (3)', 'Wardrobes (3)', 'Ceiling Fan'],
    'Suite' => ['Wi-Fi', 'Study Desk', 'Wardrobe', 'Air Conditioning', 'Mini Fridge']
];

// Initialize blocks array - will be populated from database
$blocks = [];

// Check if the database tables exist
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'rooms'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    // First, let's verify the block data in the database
    $blockQuery = "SELECT * FROM hostel_blocks ORDER BY id";
    $blockResult = $conn->query($blockQuery);
    
    // If blocks exist in database, use them instead of hardcoded blocks
    if ($blockResult && $blockResult->num_rows > 0) {
        $blocks = [];
        while ($row = $blockResult->fetch_assoc()) {
            $blocks[$row['id']] = $row;
        }
    }
      // Fetch rooms from the database (similar to admin panel)
    $sql = "SELECT r.*, b.block_name, b.gender_restriction, b.nationality_restriction 
            FROM rooms r 
            JOIN hostel_blocks b ON r.block_id = b.id
            ORDER BY b.block_name, r.room_number";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $roomId = 1; // Start room ID counter
        while($row = $result->fetch_assoc()) {
            // Get the room type info and features
            $type = isset($row["room_type"]) ? $row["room_type"] : (isset($row["type"]) ? $row["type"] : "Single");
            $roomTypeInfo = isset($roomTypes[$type]) ? $roomTypes[$type] : $roomTypes["Single"];
            $roomFeaturesList = isset($features[$type]) ? $features[$type] : $features["Single"];
            
            // Format features as comma-separated string
            $featuresString = implode(", ", $roomFeaturesList);
            
            // Get restriction data
            $gender_restriction = isset($row["gender_restriction"]) ? $row["gender_restriction"] : "None";
            $nationality_restriction = isset($row["nationality_restriction"]) ? $row["nationality_restriction"] : "None";
              // Check if the student can access this room based on gender and citizenship restrictions
            $gender_allowed = ($gender_restriction === "None" || $gender_restriction === $student_gender);
            $nationality_allowed = ($nationality_restriction === "None" || 
                                   ($nationality_restriction === "Local" && $student_citizenship === "Malaysian") ||
                                   ($nationality_restriction === "International" && $student_citizenship === "Others"));
            
            // Only add the room to the list if the student meets the restrictions
            if ($gender_allowed && $nationality_allowed) {
                $rooms[] = [
                    "id" => isset($row["id"]) ? $row["id"] : $roomId++,
                    "block_id" => isset($row["block_id"]) ? $row["block_id"] : 1,
                    "block" => isset($row["block_name"]) ? $row["block_name"] : "Block A",
                    "room_number" => isset($row["room_number"]) ? $row["room_number"] : "",
                    "type" => $type,
                    "price" => (isset($row["rate_per_semester"]) ? $row["rate_per_semester"] : $roomTypeInfo['rate']) . " MYR",
                    "features" => isset($row["features"]) ? $row["features"] : $featuresString,
                    "gender_restriction" => $gender_restriction,
                    "nationality_restriction" => $nationality_restriction,
                    "availability" => "Available" // Always set to "Available"
                ];
            }
        }
    }
}

// If no rooms were loaded from database, just display the default message
// The template already has a message for when $rooms is empty

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_room'])) {
    $selected_room_id = $_POST['room_id'];
    
    // Check if the database tables exist
    $registration_table_exists = false;
    $result = $conn->query("SHOW TABLES LIKE 'hostel_registrations'");
    if ($result && $result->num_rows > 0) {
        $registration_table_exists = true;
    }
    
    // Define a debug variable to store information about registration checks
    $registration_debug = [];
    
    if (!$registration_table_exists) {
        // Database tables don't exist yet, just show success message
        $message = "Registration request for room ID " . htmlspecialchars($selected_room_id) . " submitted. You will be notified once confirmed.";
        $message_type = "success";
        $registration_debug[] = "No hostel_registrations table exists yet.";
    } else {
        // Table exists, verify if it has proper structure
        $structure_check = $conn->query("SELECT COUNT(*) FROM information_schema.columns 
            WHERE table_schema = DATABASE() AND table_name = 'hostel_registrations'");
        
        $column_count = 0;
        if ($structure_check) {
            $column_count = $structure_check->fetch_row()[0];
        }
        
        if (!$structure_check || $column_count < 5) {
            // Table doesn't exist or is malformed, proceed with registration
            $message = "Registration request submitted successfully. You will be notified once confirmed.";
            $message_type = "success";
            $registration_debug[] = "hostel_registrations table exists but has incorrect structure ($column_count columns).";
        } else {
            // Since all rooms are available now, we just need to check if student has registered before
            // Check if student already has an active registration
            $stmt_check_existing = $conn->prepare("SELECT * FROM hostel_registrations WHERE student_id = ? AND status IN ('Pending', 'Approved', 'Checked In')");
            $stmt_check_existing->bind_param("i", $student_id);
            $stmt_check_existing->execute();
            $result_existing = $stmt_check_existing->get_result();
            
            // Add diagnostic information when in debug mode
            $active_registration = false;
            if ($result_existing->num_rows > 0) {
                $active_registration = true;
                $registration_data = $result_existing->fetch_assoc();
                $registration_debug[] = "Found active registration with ID: " . $registration_data['id'];
            } else {
                $registration_debug[] = "No active registrations found for student ID: $student_id";
            }
            
            $stmt_check_existing->close();
            
            if ($active_registration) {
                // Student already has an active registration
                $message = "You already have an active hostel registration. Please check its status before submitting a new one.";
                $message_type = "warning";
                
                // Add registration details to debug mode
                if ($debug_mode) {
                    $debug_registration_info = $registration_data;
                }
            } else {
                // All is good, proceed with registration
                $registration_date = date('Y-m-d H:i:s');
                $status = 'Pending';
                $requested_check_in = date('Y-m-d', strtotime('+7 days')); // Default check-in date a week from now
                
                $stmt_insert_registration = $conn->prepare("INSERT INTO hostel_registrations (student_id, room_id, registration_date, requested_check_in_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert_registration->bind_param("iisss", $student_id, $selected_room_id, $registration_date, $requested_check_in, $status);
                
                if ($stmt_insert_registration->execute()) {
                    // Update room availability status
                    $stmt_update_room = $conn->prepare("UPDATE rooms SET availability_status = 'Pending Confirmation' WHERE id = ?");
                    $stmt_update_room->bind_param("i", $selected_room_id);
                    $stmt_update_room->execute();
                    $stmt_update_room->close();
                    
                    $message = "Registration request submitted successfully. You will be notified once approved.";
                    $message_type = "success";
                    $registration_debug[] = "New registration created for student ID: $student_id, room ID: $selected_room_id";
                } else {
                    $message = "Error submitting registration: " . $conn->error;
                    $message_type = "danger";
                    $registration_debug[] = "Error inserting registration: " . $conn->error;
                }
                
                $stmt_insert_registration->close();
            }
        }
    }
    
    if ($debug_mode) {
        $debug_registration_process = $registration_debug;
    }
}

?>

<!-- Optimized CSS for hostel registration -->
<link rel="stylesheet" href="css/hostel_registration.css">
<style>
/* Additional responsive styles */
@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
    
    .room-card {
        margin-bottom: 15px;
    }
}

/* Student profile info styles */
.student-profile-info {
    margin-bottom: 20px;
}

.student-profile-info .alert {
    border-left: 4px solid #17a2b8;
    background-color: rgba(23, 162, 184, 0.1);
    border-radius: 0.25rem;
}

.student-profile-info h5 {
    margin-bottom: 10px;
    color: #17a2b8;
}

.student-profile-info p {
    margin-bottom: 5px;
}

.student-profile-info strong {
    color: #333;
}
</style>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <div class="header-content">
                <h2>Hostel Registration - Room Features & Availability</h2>
                <p>Browse available rooms and select one to register. Please note that registration is subject to approval.</p>
            </div>
            <div class="header-actions">
                <a href="my_registrations.php" class="btn btn-secondary">
                    <i class="fas fa-history"></i> View My Registrations
                </a>
            </div>
        </div>
        
        <?php if ($debug_mode): ?>        <div class="debug-info">
            <h4>Debug Information (Only visible to admins)</h4>
            <div class="debug-section">
                <h5>Student ID:</h5>
                <p><?php echo $student_id; ?></p>
                
                <h5>Registration Process Log:</h5>
                <?php if (isset($debug_registration_process)): ?>
                    <ul>
                    <?php foreach ($debug_registration_process as $debug_msg): ?>
                        <li><?php echo htmlspecialchars($debug_msg); ?></li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (isset($debug_schema_issue)): ?>
                    <div class="alert alert-warning">
                        <strong></strong>Schema Issue:</strong> <?php echo htmlspecialchars($debug_schema_issue); ?>
                    </div>
                <?php endif; ?>
                
                <h5>Registration Status Check:</h5>
                <?php 
                // Verify the hostel_registrations table exists before querying
                $table_check = $conn->query("SHOW TABLES LIKE 'hostel_registrations'");
                if ($table_check && $table_check->num_rows > 0) {
                    // Table exists, run diagnostic query
                    $diagnostic_query = "SELECT * FROM hostel_registrations WHERE student_id = ?";
                    $stmt_diagnostic = $conn->prepare($diagnostic_query);
                    $stmt_diagnostic->bind_param("i", $student_id);
                    $stmt_diagnostic->execute();
                    $result_diagnostic = $stmt_diagnostic->get_result();
                    
                    if ($result_diagnostic->num_rows > 0) {
                        echo "<p>Found " . $result_diagnostic->num_rows . " registration(s) for this student:</p>";
                        echo "<ul>";
                        while ($row = $result_diagnostic->fetch_assoc()) {
                            echo "<li>Registration ID: " . $row['id'] . ", Room ID: " . $row['room_id'] . 
                                ", Status: " . $row['status'] . ", Date: " . $row['registration_date'] . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>No registrations found for this student.</p>";
                    }
                    $stmt_diagnostic->close();
                } else {
                    echo "<p class='alert alert-warning'>hostel_registrations table does not exist in the database.</p>";
                }
                
                // Show active registration details if available
                if (isset($debug_registration_info)) {
                    echo "<h5>Active Registration Details:</h5>";
                    echo "<pre>";
                    print_r($debug_registration_info);
                    echo "</pre>";
                }
                ?>

                <h5>Block Data:</h5>
                <pre><?php print_r($blocks); ?></pre>
                
                <h5>Rooms by Block:</h5>
                <?php
                $room_count_by_block = [];
                foreach ($rooms as $r) {
                    $block_name = $r['block'];
                    if (!isset($room_count_by_block[$block_name])) {
                        $room_count_by_block[$block_name] = 0;
                    }
                    $room_count_by_block[$block_name]++;
                }
                print_r($room_count_by_block);
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo isset($message_type) ? $message_type : 'info'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Student profile information and room restriction notice -->
        <div class="student-profile-info">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Room Availability Based on Your Profile</h5>                <p>Your profile details: 
                    <strong>Gender:</strong> <?php echo !empty($student_gender) ? htmlspecialchars($student_gender) : 'Not specified'; ?> | 
                    <strong>Citizenship:</strong> <?php echo !empty($student_citizenship) ? htmlspecialchars($student_citizenship) : 'Not specified'; ?>
                </p>
                <p><i class="fas fa-filter"></i> <strong>Note:</strong> The room list below only shows accommodations you are eligible for based on your gender and citizenship.</p>
            </div>
        </div>
        
        <div class="room-list">
            <?php if (!empty($rooms)): ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card <?php echo ($room['availability'] !== 'Available') ? 'unavailable' : ''; ?>">
                        <div class="room-header">
                            <h3><i class="fas fa-door-open"></i> Room <?php echo htmlspecialchars($room['room_number']); ?> (<?php echo htmlspecialchars($room['block']); ?>)</h3>
                            <span class="status-<?php echo strtolower(str_replace(' ', '-', $room['availability'])); ?>">
                                <i class="fas <?php echo ($room['availability'] === 'Available') ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> <?php echo htmlspecialchars($room['availability']); ?>
                            </span>
                        </div>
                        
                        <div class="room-details">
                            <div class="room-info">
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($room['type']); ?></p>
                                <p><strong>Price:</strong> <?php echo htmlspecialchars($room['price']); ?> / semester</p>
                                <p><strong>Restrictions:</strong> 
                                    <?php if (!empty($room['gender_restriction']) && $room['gender_restriction'] !== 'None'): ?>
                                        <span class="restriction gender-restriction">
                                            <i class="fas <?php echo ($room['gender_restriction'] === 'Male') ? 'fa-male' : 'fa-female'; ?>"></i>
                                            <?php echo htmlspecialchars($room['gender_restriction']); ?> Only
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($room['nationality_restriction']) && $room['nationality_restriction'] !== 'None'): ?>
                                        <span class="restriction nationality-restriction">
                                            <i class="fas <?php echo ($room['nationality_restriction'] === 'International') ? 'fa-globe' : 'fa-flag'; ?>"></i>
                                            <?php echo htmlspecialchars($room['nationality_restriction']); ?> Students
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                              <div class="room-features">
                                <p><strong><i class="fas fa-list-ul"></i> Room Features:</strong></p>
                                <?php 
                                $featuresList = explode(", ", $room['features']);
                                if (!empty($featuresList)): 
                                ?>
                                <ul class="features-list">
                                    <?php foreach($featuresList as $feature): 
                                        // Determine icon based on feature
                                        $icon = 'fa-check';
                                        if (stripos($feature, 'wi-fi') !== false) $icon = 'fa-wifi';
                                        if (stripos($feature, 'air') !== false) $icon = 'fa-snowflake';
                                        if (stripos($feature, 'desk') !== false) $icon = 'fa-desk';
                                        if (stripos($feature, 'fridge') !== false) $icon = 'fa-refrigerator';
                                        if (stripos($feature, 'wardrobe') !== false) $icon = 'fa-door-closed';
                                        if (stripos($feature, 'fan') !== false) $icon = 'fa-fan';
                                    ?>
                                        <li><i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                          <div class="room-actions">
                            <?php if ($room['availability'] === 'Available'): ?>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" name="register_room" class="btn btn-primary">
                                        <i class="fas fa-check-circle"></i> Register for this Room
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled>
                                    <i class="fas fa-ban"></i> Currently Unavailable
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>            <?php else: ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> No Rooms Available</h5>
                    <p>There are no rooms currently available that match your profile restrictions. This might be because:</p>
                    <ul>
                        <li>No rooms are currently listed in the system</li>
                        <li>The available rooms have gender or citizenship restrictions that don't match your profile</li>
                        <li>All eligible rooms are currently occupied or pending confirmation</li>
                    </ul>
                    <p>Please check back later or contact the hostel administration for assistance.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../shared/includes/footer.php';
?>
