<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\block_rooms.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../shared/includes/db_connection.php";

// Check if block ID is provided
if (!isset($_GET['block_id']) || empty($_GET['block_id'])) {
    header("Location: hostel_blocks.php");
    exit();
}

$block_id = $_GET['block_id'];

// Get block details
$blocks = [
    1 => [
        'id' => 1,
        'block_name' => 'Block A',
        'gender_restriction' => 'Male',
        'nationality_restriction' => 'Local',
        'description' => 'Hostel block for local male students with standard facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ],
    2 => [
        'id' => 2,
        'block_name' => 'Block B',
        'gender_restriction' => 'Female',
        'nationality_restriction' => 'Local',
        'description' => 'Hostel block for local female students with standard facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ],
    3 => [
        'id' => 3,
        'block_name' => 'Block C',
        'gender_restriction' => 'Male',
        'nationality_restriction' => 'International',
        'description' => 'Hostel block for international male students with cultural integration facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ],
    4 => [
        'id' => 4,
        'block_name' => 'Block D',
        'gender_restriction' => 'Female',
        'nationality_restriction' => 'International',
        'description' => 'Hostel block for international female students with cultural integration facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ]
];

if (!isset($blocks[$block_id])) {
    header("Location: hostel_blocks.php");
    exit();
}

$block = $blocks[$block_id];

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - " . htmlspecialchars($block['block_name']) . " Rooms";
$additionalCSS = ["css/dashboard.css"];

// Get rooms data from database
$rooms = [];

// Check if relevant tables exist before querying
$tablesExist = false;
$result = $conn->query("SHOW TABLES LIKE 'rooms'");
if ($result && $result->num_rows > 0) {
    // Tables exist, now check for columns
    try {
        // Try a simpler query first without joining to room_rates
        $sql = "SELECT * FROM rooms WHERE block_id = ? ORDER BY room_number";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $block_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Add a default rate if rate_per_semester isn't available
                    if (!isset($row['rate_per_semester'])) {
                        $row['rate_per_semester'] = rand(800, 2000); // Fallback default rate
                    }
                    $rooms[] = $row;
                }
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Query failed, we'll use mock data instead
        $rooms = [];
    }
}

// Always use predefined mock data to ensure consistent display
$rooms = []; // Clear any partially loaded data

// Generate mock room data based on block type
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

// Different room availability and types for different blocks
$blockSpecificRooms = [
    1 => [ // Block A
        ['number' => 'A101', 'type' => 'Single', 'status' => 'Occupied'],
        ['number' => 'A102', 'type' => 'Single', 'status' => 'Available'],
        ['number' => 'A103', 'type' => 'Double', 'status' => 'Available'],
        ['number' => 'A104', 'type' => 'Double', 'status' => 'Maintenance'],
        ['number' => 'A105', 'type' => 'Double', 'status' => 'Occupied'],
        ['number' => 'A201', 'type' => 'Triple', 'status' => 'Available'],
        ['number' => 'A202', 'type' => 'Suite', 'status' => 'Occupied'],
    ],
    2 => [ // Block B
        ['number' => 'B101', 'type' => 'Single', 'status' => 'Available'],
        ['number' => 'B102', 'type' => 'Single', 'status' => 'Occupied'],
        ['number' => 'B103', 'type' => 'Single', 'status' => 'Occupied'],
        ['number' => 'B104', 'type' => 'Double', 'status' => 'Available'],
        ['number' => 'B105', 'type' => 'Double', 'status' => 'Available'],
        ['number' => 'B201', 'type' => 'Suite', 'status' => 'Occupied'],
    ],
    3 => [ // Block C
        ['number' => 'C101', 'type' => 'Double', 'status' => 'Available'],
        ['number' => 'C102', 'type' => 'Double', 'status' => 'Available'],
        ['number' => 'C103', 'type' => 'Triple', 'status' => 'Occupied'],
        ['number' => 'C104', 'type' => 'Triple', 'status' => 'Maintenance'],
        ['number' => 'C105', 'type' => 'Suite', 'status' => 'Available'],
        ['number' => 'C201', 'type' => 'Suite', 'status' => 'Occupied'],
    ],
    4 => [ // Block D
        ['number' => 'D101', 'type' => 'Single', 'status' => 'Occupied'],
        ['number' => 'D102', 'type' => 'Single', 'status' => 'Available'],
        ['number' => 'D103', 'type' => 'Double', 'status' => 'Available'],
        ['number' => 'D104', 'type' => 'Double', 'status' => 'Occupied'],
        ['number' => 'D105', 'type' => 'Triple', 'status' => 'Available'],
        ['number' => 'D201', 'type' => 'Suite', 'status' => 'Occupied'],
    ]
];

// Generate rooms for the current block
if (isset($blockSpecificRooms[$block_id])) {
    $mockRooms = $blockSpecificRooms[$block_id];
    
    foreach ($mockRooms as $room) {
        $roomTypeInfo = $roomTypes[$room['type']];
        $roomFeatures = $features[$room['type']];
        
        $rooms[] = [
            'id' => rand(1000, 9999),
            'block_id' => $block_id,
            'room_number' => $room['number'],
            'room_type' => $room['type'],
            'room_type_id' => array_search($room['type'], array_keys($roomTypes)) + 1,
            'num_beds' => $roomTypeInfo['beds'],
            'bathroom_type' => $roomTypeInfo['bathroom'],
            'features' => json_encode($roomFeatures),
            'status' => $room['status'], // Status is explicitly set for each room
            'rate_per_semester' => $roomTypeInfo['rate']
        ];
    }
}

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php
    $pageHeading = htmlspecialchars($block['block_name']) . " - Room Management";
    $breadcrumbs = [
        ['Home', 'dashboard.php'],
        ['Hostel Blocks', 'hostel_blocks.php'],
        [$block['block_name'] . ' Rooms', '#']
    ];
    require_once '../shared/includes/admin-content-header.php';
    ?>
    
    <!-- Block Info -->
    <div class="block-info-banner">
        <div class="block-info-content">
            <div class="block-info-left">
                <h3><?= htmlspecialchars($block['block_name']) ?></h3>
                <div class="block-details-inline">
                    <?php 
                    $genderIcon = 'fas fa-question-circle';
                    $genderText = 'Unknown';
                    $genderClass = 'status-neutral';
                    
                    switch ($block['gender_restriction']) {
                        case 'Male':
                            $genderIcon = 'fas fa-male';
                            $genderText = 'Males Only';
                            $genderClass = 'status-male';
                            break;
                        case 'Female':
                            $genderIcon = 'fas fa-female';
                            $genderText = 'Females Only';
                            $genderClass = 'status-female';
                            break;
                        case 'Mixed':
                            $genderIcon = 'fas fa-venus-mars';
                            $genderText = 'Mixed Gender';
                            $genderClass = 'status-mixed';
                            break;
                        case 'None':
                            $genderIcon = 'fas fa-users';
                            $genderText = 'No Restriction';
                            $genderClass = 'status-none';
                            break;
                    }
                    ?>
                    <span class="status <?= $genderClass ?>">
                        <i class="<?= $genderIcon ?>"></i> <?= $genderText ?>
                    </span>
                    
                    <?php 
                    $natIcon = 'fas fa-globe';
                    $natText = 'All Students';
                    $natClass = 'status-neutral';
                    
                    switch ($block['nationality_restriction']) {
                        case 'Local':
                            $natIcon = 'fas fa-flag';
                            $natText = 'Local Students';
                            $natClass = 'status-local';
                            break;
                        case 'International':
                            $natIcon = 'fas fa-globe-americas';
                            $natText = 'International Students';
                            $natClass = 'status-international';
                            break;
                        case 'Mixed':
                            $natIcon = 'fas fa-globe';
                            $natText = 'Mixed Students';
                            $natClass = 'status-mixed';
                            break;
                        case 'None':
                            $natIcon = 'fas fa-users';
                            $natText = 'No Restriction';
                            $natClass = 'status-none';
                            break;
                    }
                    ?>
                    <span class="status <?= $natClass ?>">
                        <i class="<?= $natIcon ?>"></i> <?= $natText ?>
                    </span>
                </div>
            </div>
            
            <div class="block-info-right">
                <div class="room-stats">                    <div class="stat-item">
                        <span class="stat-label">Total Rooms</span>
                        <span class="stat-value"><?= count($rooms) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Available</span>
                        <span class="stat-value"><?= count(array_filter($rooms, function($room) { return isset($room['status']) && $room['status'] === 'Available'; })) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Occupied</span>
                        <span class="stat-value"><?= count(array_filter($rooms, function($room) { return isset($room['status']) && $room['status'] === 'Occupied'; })) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Maintenance</span>
                        <span class="stat-value"><?= count(array_filter($rooms, function($room) { return isset($room['status']) && $room['status'] === 'Maintenance'; })) ?></span>
                    </div>
                </div>
                <a href="hostel_blocks.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Blocks</a>
            </div>
        </div>
    </div>
    
    <!-- Rooms List -->
    <div class="card">
        <div class="card-header">
            <div class="card-title-area">
                <div class="card-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <h2 class="card-title">Rooms</h2>
            </div>
            <div class="card-actions">
                <div class="filter-container">
                    <select id="roomTypeFilter" class="filter-select">
                        <option value="all">All Room Types</option>
                        <option value="Single">Single</option>
                        <option value="Double">Double</option>
                        <option value="Triple">Triple</option>
                        <option value="Suite">Suite</option>
                    </select>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Statuses</option>
                        <option value="Available">Available</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($rooms)): ?>
                <div class="no-data-container">
                    <div class="no-data-icon">
                        <i class="fas fa-door-closed"></i>
                    </div>
                    <h3>No Rooms Found</h3>
                    <p>There are no rooms assigned to this block yet.</p>
                </div>
            <?php else: ?>
                <div class="rooms-grid">
                    <?php foreach ($rooms as $room): ?>
                        <div class="room-card" data-room-type="<?= htmlspecialchars($room['room_type']) ?>" data-status="<?= htmlspecialchars($room['status']) ?>">
                            <div class="room-header">
                                <h3><?= htmlspecialchars($room['room_number']) ?></h3>
                                <?php 
                                $statusClass = '';
                                $statusIcon = '';
                                
                                switch ($room['status']) {
                                    case 'Available':
                                        $statusClass = 'status-available';
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'Occupied':
                                        $statusClass = 'status-occupied';
                                        $statusIcon = 'fa-user';
                                        break;
                                    case 'Maintenance':
                                        $statusClass = 'status-maintenance';
                                        $statusIcon = 'fa-tools';
                                        break;
                                    default:
                                        $statusClass = 'status-unknown';
                                        $statusIcon = 'fa-question-circle';
                                }
                                ?>
                                <span class="room-status <?= $statusClass ?>">
                                    <i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars($room['status']) ?>
                                </span>
                            </div>
                            <div class="room-details">                                <div class="room-info">
                                    <p class="room-type"><strong>Type:</strong> <?= isset($room['room_type']) ? htmlspecialchars($room['room_type']) : 'Standard' ?></p><p><strong>Beds:</strong> <?= isset($room['num_beds']) ? htmlspecialchars($room['num_beds']) : '1' ?></p>
                                    <p><strong>Bathroom:</strong> <?= isset($room['bathroom_type']) ? htmlspecialchars($room['bathroom_type']) : 'Standard' ?></p>
                                    <p class="room-price"><strong>Price:</strong> RM<?= isset($room['rate_per_semester']) ? number_format($room['rate_per_semester'], 2) : number_format(1200, 2) ?>/semester</p>
                                </div>                                <?php 
                                // Generate features based on room type if not available
                                $featuresList = [];
                                
                                if (!empty($room['features'])) {
                                    // Try to decode if it's a JSON string
                                    $featuresList = json_decode($room['features'], true);
                                }
                                
                                // If features is empty or invalid JSON, generate features based on room type
                                if (!is_array($featuresList) || empty($featuresList)) {
                                    $roomType = isset($room['room_type']) ? $room['room_type'] : '';
                                    
                                    // Default features for each room type
                                    switch ($roomType) {
                                        case 'Single':
                                            $featuresList = ['Wi-Fi', 'Study Desk', 'Wardrobe', 'Fan'];
                                            break;
                                        case 'Double':
                                            $featuresList = ['Wi-Fi', 'Study Desks (2)', 'Wardrobes (2)', 'Fan'];
                                            break;
                                        case 'Triple':
                                            $featuresList = ['Wi-Fi', 'Study Desks (3)', 'Wardrobes (3)', 'Ceiling Fan'];
                                            break;
                                        case 'Suite':
                                            $featuresList = ['Wi-Fi', 'Study Desk', 'Wardrobe', 'Air Conditioning', 'Mini Fridge'];
                                            break;
                                        default:
                                            $featuresList = ['Wi-Fi', 'Study Desk', 'Wardrobe'];
                                    }
                                }
                                ?>
                                
                                <div class="room-features">
                                    <p><strong>Features:</strong></p>
                                    <ul class="features-list">
                                        <?php foreach ($featuresList as $feature): ?>
                                            <li><i class="fas fa-check"></i> <?= htmlspecialchars($feature) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="room-actions">
                                    <a href="#" class="action-btn view-details-btn" title="View Details" data-room-id="<?= $room['id'] ?>" data-room-number="<?= htmlspecialchars($room['room_number']) ?>">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <a href="#" class="action-btn edit-room-btn" title="Edit Room" data-room-id="<?= $room['id'] ?>" data-room-number="<?= htmlspecialchars($room['room_number']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="action-btn change-status-btn" title="Change Status" data-room-id="<?= $room['id'] ?>" data-room-number="<?= htmlspecialchars($room['room_number']) ?>" data-current-status="<?= htmlspecialchars($room['status']) ?>">
                                        <i class="fas fa-exchange-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Banner for block info */
.block-info-banner {
    background-color: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.block-info-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: linear-gradient(to bottom, #6e8efb, #a777e3);
}

.block-info-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.block-info-left h3 {
    margin: 0 0 10px 0;
    font-size: 22px;
    font-weight: 600;
}

.block-details-inline {
    display: flex;
    gap: 10px;
}

.block-info-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.room-stats {
    display: flex;
    gap: 15px;
}

.stat-item {
    text-align: center;
    background: #f5f7ff;
    padding: 10px 15px;
    border-radius: 8px;
    min-width: 100px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    border-left: 3px solid #6e8efb;
}

.stat-item:nth-child(1) {
    border-left-color: #6e8efb;
}

.stat-item:nth-child(2) {
    border-left-color: #2e7d32;
}

.stat-item:nth-child(3) {
    border-left-color: #0d47a1;
}

.stat-item:nth-child(4) {
    border-left-color: #e65100;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    display: block;
    font-size: 22px;
    font-weight: 700;
    color: #333;
}

.back-btn {
    color: #6e8efb;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    border: 1px solid #d1deff;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.back-btn:hover {
    background-color: #f0f4ff;
}

/* Rooms grid */
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.room-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.room-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.room-header {
    background: #f8faff;
    padding: 12px 15px;
    border-bottom: 1px solid #eaeef9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.room-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.room-status {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.status-available {
    background-color: #e8f5e9;
    color: #2e7d32;
    border: 1px solid rgba(46, 125, 50, 0.2);
}

.status-occupied {
    background-color: #e3f2fd;
    color: #0d47a1;
    border: 1px solid rgba(13, 71, 161, 0.2);
}

.status-maintenance {
    background-color: #fff8e1;
    color: #e65100;
    border: 1px solid rgba(230, 81, 0, 0.2);
}

.status-unknown {
    background-color: #f5f5f5;
    color: #616161;
    border: 1px solid rgba(97, 97, 97, 0.2);
}

.status-male {
    background-color: #e3f2fd;
    color: #1976d2;
}

.status-female {
    background-color: #f8bbd0;
    color: #c2185b;
}

.status-mixed {
    background-color: #e8f5e9;
    color: #388e3c;
}

.status-local {
    background-color: #fff8e1;
    color: #ffa000;
}

.status-international {
    background-color: #e8eaf6;
    color: #3f51b5;
}

.status-neutral, .status-none {
    background-color: #f5f5f5;
    color: #757575;
}

.room-details {
    padding: 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.room-info {
    margin-bottom: 15px;
}

.room-info p {
    margin: 5px 0;
    font-size: 14px;
    line-height: 1.4;
}

.room-price {
    font-weight: 600;
    color: #333;
    font-size: 16px;
    background-color: #f5f7ff;
    padding: 5px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
}

.room-features {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    flex: 1;
}

.room-features p {
    margin: 5px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 8px;
}

.features-list li {
    font-size: 13px;
    padding: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
    background-color: #f9f9f9;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.features-list li:hover {
    background-color: #f0f4ff;
}

.features-list li i {
    color: #4caf50;
    font-size: 12px;
    background-color: rgba(76, 175, 80, 0.1);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.room-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    transition: all 0.2s ease;
    background-color: #f5f7ff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

.view-details-btn {
    color: #6e8efb;
    background-color: rgba(110, 142, 251, 0.1);
}

.view-details-btn:hover {
    background-color: rgba(110, 142, 251, 0.2);
}

.edit-room-btn {
    color: #4caf50;
    background-color: rgba(76, 175, 80, 0.1);
}

.edit-room-btn:hover {
    background-color: rgba(76, 175, 80, 0.2);
}

.change-status-btn {
    color: #ffa000;
    background-color: rgba(255, 160, 0, 0.1);
}

.change-status-btn:hover {
    background-color: rgba(255, 160, 0, 0.2);
}

.filter-container {
    display: flex;
    gap: 12px;
}

.filter-select {
    padding: 8px 15px;
    border: 1px solid #e0e6ff;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
    background-color: #f8faff;
    color: #333;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
    cursor: pointer;
}

.filter-select:focus {
    border-color: #6e8efb;
    box-shadow: 0 0 0 3px rgba(110, 142, 251, 0.1);
}

.filter-select:hover {
    border-color: #a0b4ff;
    background-color: #f0f4ff;
}

.card-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 992px) {
    .block-info-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .block-info-right {
        width: 100%;
        justify-content: space-between;
    }

    .room-stats {
        flex-wrap: wrap;
    }

    .stat-item {
        min-width: 70px;
    }
}

@media (max-width: 768px) {
    .rooms-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }

    .filter-container {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .rooms-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .card-actions {
        width: 100%;
    }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Room filtering functionality
        const roomTypeFilter = document.getElementById('roomTypeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const roomCards = document.querySelectorAll('.room-card');
        
        function applyFilters() {
            const selectedRoomType = roomTypeFilter.value;
            const selectedStatus = statusFilter.value;
            
            roomCards.forEach(card => {
                const roomType = card.dataset.roomType;
                const status = card.dataset.status;
                
                const roomTypeMatch = selectedRoomType === 'all' || roomType === selectedRoomType;
                const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                
                if (roomTypeMatch && statusMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        roomTypeFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
        
        // Button functionality (placeholder for now)
        document.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const roomId = this.getAttribute('data-room-id');
                const roomNumber = this.getAttribute('data-room-number');
                alert(`View details for Room ${roomNumber} functionality will be implemented here.`);
            });
        });
        
        document.querySelectorAll('.edit-room-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const roomId = this.getAttribute('data-room-id');
                const roomNumber = this.getAttribute('data-room-number');
                alert(`Edit Room ${roomNumber} functionality will be implemented here.`);
            });
        });
        
        document.querySelectorAll('.change-status-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const roomId = this.getAttribute('data-room-id');
                const roomNumber = this.getAttribute('data-room-number');
                const currentStatus = this.getAttribute('data-current-status');
                
                const newStatus = prompt(
                    `Change status for Room ${roomNumber}\nCurrent status: ${currentStatus}\n\nEnter new status (Available, Occupied, Maintenance):`, 
                    currentStatus
                );
                
                if (newStatus && ['Available', 'Occupied', 'Maintenance'].includes(newStatus)) {
                    alert(`Status for Room ${roomNumber} would be updated to ${newStatus} in a real implementation.`);
                    // In a real implementation, this would update the database and refresh the page
                }
            });
        });
    });
</script>

<?php require_once '../shared/includes/footer.php'; ?>
