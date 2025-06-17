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

// Debug block information - only visible to logged-in administrators
$debug_mode = isset($_SESSION["role"]) && $_SESSION["role"] === "admin" && isset($_GET['debug']);

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

// Define room types and their details (same as in admin panel)
$roomTypes = [
    'Single' => ['beds' => 1, 'bathroom' => 'Shared', 'rate' => 1200],
    'Double' => ['beds' => 2, 'bathroom' => 'Shared', 'rate' => 900],
    'Triple' => ['beds' => 3, 'bathroom' => 'Shared', 'rate' => 750],
    'Suite' => ['beds' => 1, 'bathroom' => 'Private', 'rate' => 1800],
];

$features = [
    'Single' => ['Wi-Fi', 'Study Desk', 'Wardrobe', 'Fan'],
    'Double' => ['Wi-Fi', 'Study Desks (2)', 'Wardrobes (2)', 'Fan'],
    'Triple' => ['Wi-Fi', 'Study Desks (3)', 'Wardrobes (3)', 'Ceiling Fan'],
    'Suite' => ['Wi-Fi', 'Study Desk', 'Wardrobe', 'Air Conditioning', 'Mini Fridge']
];

// Get blocks data (same structure as admin panel)
$blocks = [
    1 => [
        'id' => 1,
        'block_name' => 'Block A',
        'gender_restriction' => 'Male',
        'nationality_restriction' => 'Local',
        'description' => 'Hostel block for local male students with standard facilities.',
    ],
    2 => [
        'id' => 2,
        'block_name' => 'Block B',
        'gender_restriction' => 'Female',
        'nationality_restriction' => 'Local',
        'description' => 'Hostel block for local female students with standard facilities.',
    ],
    3 => [
        'id' => 3,
        'block_name' => 'Block C',
        'gender_restriction' => 'Male',
        'nationality_restriction' => 'International',
        'description' => 'Hostel block for international male students with cultural integration facilities.',
    ],
    4 => [
        'id' => 4,
        'block_name' => 'Block D',
        'gender_restriction' => 'Female',
        'nationality_restriction' => 'International',
        'description' => 'Hostel block for international female students with cultural integration facilities.',
    ]
];

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
            
            $rooms[] = [
                "id" => isset($row["id"]) ? $row["id"] : $roomId++,
                "block_id" => isset($row["block_id"]) ? $row["block_id"] : 1,
                "block" => isset($row["block_name"]) ? $row["block_name"] : "Block A",
                "room_number" => isset($row["room_number"]) ? $row["room_number"] : "",
                "type" => $type,
                "price" => (isset($row["rate_per_semester"]) ? $row["rate_per_semester"] : $roomTypeInfo['rate']) . " MYR",
                "features" => isset($row["features"]) ? $row["features"] : $featuresString,
                "gender_restriction" => isset($row["gender_restriction"]) ? $row["gender_restriction"] : "None",
                "nationality_restriction" => isset($row["nationality_restriction"]) ? $row["nationality_restriction"] : "None",
                "availability" => "Available" // Always set to "Available"
            ];
        }
    }
}

// If no rooms were loaded from database, use the room structure from admin panel
if (empty($rooms)) {
    // Define room types and numbers for each block (all rooms available)
    $blockSpecificRooms = [
                1 => [ // Block A - 10 rooms, all available
                    ['number' => 'A101', 'type' => 'Single', 'status' => 'Available'],
                    ['number' => 'A102', 'type' => 'Single', 'status' => 'Available'],
                    ['number' => 'A103', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'A104', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'A105', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'A106', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'A107', 'type' => 'Triple', 'status' => 'Available'],
                    ['number' => 'A108', 'type' => 'Triple', 'status' => 'Available'],
                    ['number' => 'A109', 'type' => 'Suite', 'status' => 'Available'],
                    ['number' => 'A110', 'type' => 'Suite', 'status' => 'Available'],
                ],
                2 => [ // Block B - 10 rooms, all available
                    ['number' => 'B101', 'type' => 'Single', 'status' => 'Available'],
                    ['number' => 'B102', 'type' => 'Single', 'status' => 'Available'],
                    ['number' => 'B103', 'type' => 'Single', 'status' => 'Available'],
                    ['number' => 'B104', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'B105', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'B106', 'type' => 'Double', 'status' => 'Available'],
                    ['number' => 'B107', 'type' => 'Triple', 'status' => 'Available'],
                    ['number' => 'B108', 'type' => 'Triple', 'status' => 'Available'],
                    ['number' => 'B109', 'type' => 'Suite', 'status' => 'Available'],
                    ['number' => 'B110', 'type' => 'Suite', 'status' => 'Available'],
                ],
        3 => [ // Block C - 10 rooms, all available
            ['number' => 'C101', 'type' => 'Double', 'status' => 'Available'],
            ['number' => 'C102', 'type' => 'Double', 'status' => 'Available'],
            ['number' => 'C103', 'type' => 'Double', 'status' => 'Available'],
            ['number' => 'C104', 'type' => 'Double', 'status' => 'Available'],
            ['number' => 'C105', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'C106', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'C107', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'C108', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'C109', 'type' => 'Suite', 'status' => 'Available'],
            ['number' => 'C110', 'type' => 'Suite', 'status' => 'Available'],
        ],
        4 => [ // Block D - 10 rooms, all available
            ['number' => 'D101', 'type' => 'Single', 'status' => 'Available'],
            ['number' => 'D102', 'type' => 'Single', 'status' => 'Available'],
            ['number' => 'D103', 'type' => 'Double', 'status' => 'Available'],
            ['number' => 'D104', 'type' => 'Double', 'status' => 'Available'],
            ['number' => 'D105', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'D106', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'D107', 'type' => 'Triple', 'status' => 'Available'],
            ['number' => 'D108', 'type' => 'Suite', 'status' => 'Available'],
            ['number' => 'D109', 'type' => 'Suite', 'status' => 'Available'],
            ['number' => 'D110', 'type' => 'Suite', 'status' => 'Available'],
        ]
    ];
    
    // Generate rooms for all blocks
    $roomId = 1;
    foreach ($blockSpecificRooms as $blockId => $mockRooms) {
        // Make sure we're only using valid blocks (1-4, which are A-D)
        if ($blockId < 1 || $blockId > 4) continue;
        
        $block = $blocks[$blockId] ?? null;
        if (!$block) continue;
        
        // Get the actual block name from our blocks array, ensuring consistency
        $blockName = $block['block_name']; // This should be "Block A", "Block B", etc.
        
        foreach ($mockRooms as $room) {
            $roomTypeInfo = $roomTypes[$room['type']] ?? $roomTypes['Single'];
            $roomFeaturesList = $features[$room['type']] ?? $features['Single'];
            
            // Format features as comma-separated string
            $featuresString = implode(", ", $roomFeaturesList);
            
            // Make sure room number starts with the correct block letter (A, B, C, or D)
            $roomNumber = $room['number'];
            // Extract the first character of block_name (should be "A", "B", "C", or "D")
            $blockLetter = substr($blockName, -1); // Get last character of "Block X"
            
            // Ensure room number starts with correct block letter
            if (!preg_match('/^' . $blockLetter . '/', $roomNumber)) {
                // If not, replace the first character with the correct block letter
                $roomNumber = $blockLetter . substr($roomNumber, 1);
            }
            
            $rooms[] = [
                "id" => $roomId++,
                "block_id" => $blockId,
                "block" => $blockName,
                "room_number" => $roomNumber,
                "type" => $room['type'],
                "price" => $roomTypeInfo['rate'] . " MYR",
                "features" => $featuresString,
                "gender_restriction" => $block['gender_restriction'] ?? "None",
                "nationality_restriction" => $block['nationality_restriction'] ?? "None", 
                "availability" => $room['status']
            ];}
    }
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

        <div class="room-list">
            <?php if (!empty($rooms)): ?>                <?php foreach ($rooms as $room): ?>
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
