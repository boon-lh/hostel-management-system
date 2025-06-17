<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\block_rooms.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../shared/includes/db_connection.php";

// Reset all rooms to "Available" status if DB tables exist
function resetAllRoomsToAvailable($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'rooms'");
    if ($result && $result->num_rows > 0) {
        // Check column name first (status or availability_status)
        $result = $conn->query("SHOW COLUMNS FROM rooms LIKE 'status'");
        if ($result && $result->num_rows > 0) {
            // If status column exists
            $conn->query("UPDATE rooms SET status = 'Available' WHERE 1");
        } else {
            // Try with availability_status column
            $result = $conn->query("SHOW COLUMNS FROM rooms LIKE 'availability_status'");
            if ($result && $result->num_rows > 0) {
                $conn->query("UPDATE rooms SET availability_status = 'Available' WHERE 1");
            }
        }
    }
}

// Call the function to reset all rooms
resetAllRoomsToAvailable($conn);

// Check if block ID is provided
if (!isset($_GET['block_id']) || empty($_GET['block_id'])) {
    header("Location: hostel_blocks.php");
    exit();
}

$block_id = $_GET['block_id'];

// Get block details from database
$blockQuery = "SELECT * FROM hostel_blocks WHERE id = ?";
$blockStmt = $conn->prepare($blockQuery);
$blockStmt->bind_param("i", $block_id);
$blockStmt->execute();
$blockResult = $blockStmt->get_result();

if ($blockResult->num_rows === 0) {
    header("Location: hostel_blocks.php");
    exit();
}

$block = $blockResult->fetch_assoc();
$blockStmt->close();

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - " . htmlspecialchars($block['block_name']) . " Rooms";
$additionalCSS = ["css/block_rooms.css", "css/dashboard.css"];

// Get rooms data from database
$rooms = [];

// Query to get rooms for this block
$roomsQuery = "SELECT * FROM rooms WHERE block_id = ? ORDER BY room_number";
$roomsStmt = $conn->prepare($roomsQuery);

if ($roomsStmt) {
    $roomsStmt->bind_param("i", $block_id);
    $roomsStmt->execute();
    $roomsResult = $roomsStmt->get_result();
    
    if ($roomsResult && $roomsResult->num_rows > 0) {
        while ($row = $roomsResult->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    $roomsStmt->close();
}

// If no rooms found for this block, show an empty array
if (empty($rooms)) {
    $rooms = [];
}// No mock data needed anymore, we're using real database data

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
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
    require_once 'admin-content-header.php';
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
                <div class="room-stats">                    
                    <div class="stat-item">
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
                    <?php foreach ($rooms as $room): ?>                        <div class="room-card" data-room-type="<?= htmlspecialchars($room['type']) ?>" data-status="<?= htmlspecialchars($room['availability_status']) ?>">
                            <div class="room-header">
                                <h3><?= htmlspecialchars($room['room_number']) ?></h3>
                                <?php 
                                $statusClass = '';
                                $statusIcon = '';
                                
                                switch ($room['availability_status']) {
                                    case 'Available':
                                        $statusClass = 'status-available';
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'Occupied':
                                        $statusClass = 'status-occupied';
                                        $statusIcon = 'fa-user';
                                        break;
                                    case 'Pending Confirmation':
                                        $statusClass = 'status-pending';
                                        $statusIcon = 'fa-clock';
                                        break;
                                    case 'Under Maintenance':
                                        $statusClass = 'status-maintenance';
                                        $statusIcon = 'fa-tools';
                                        break;
                                    case 'Reserved':
                                        $statusClass = 'status-reserved';
                                        $statusIcon = 'fa-bookmark';
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
