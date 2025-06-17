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
                        <span class="stat-value"><?= count(array_filter($rooms, function($room) { return $room['availability_status'] === 'Available'; })) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Occupied</span>
                        <span class="stat-value"><?= count(array_filter($rooms, function($room) { return $room['availability_status'] === 'Occupied'; })) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Maintenance</span>
                        <span class="stat-value"><?= count(array_filter($rooms, function($room) { return $room['availability_status'] === 'Under Maintenance'; })) ?></span>
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
            <div class="card-actions">                <div class="filter-container">
                    <select id="roomTypeFilter" class="filter-select">
                        <option value="all">All Room Types</option>
                        <option value="Single">Single</option>
                        <option value="Double">Double</option>
                        <option value="Triple">Triple</option>
                    </select>                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Statuses</option>
                        <option value="Available">Available</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Under Maintenance">Under Maintenance</option>
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
                <div class="rooms-grid">                    <?php foreach ($rooms as $room): ?>                        <div class="room-card" data-room-type="<?= htmlspecialchars($room['type']) ?>" data-status="<?= htmlspecialchars($room['availability_status']) ?>">
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
                                    case 'Under Maintenance':
                                        $statusClass = 'status-maintenance';
                                        $statusIcon = 'fa-tools';
                                        break;
                                    default:
                                        $statusClass = 'status-unknown';
                                        $statusIcon = 'fa-question-circle';
                                }
                                ?>
                                <span class="room-status <?= $statusClass ?>">
                                    <i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars($room['availability_status']) ?>
                                </span>
                            </div>
                            <div class="room-details">
                                <div class="room-info">
                                    <p class="room-type"><strong>Type:</strong> <?= htmlspecialchars($room['type']) ?></p>
                                    <p><strong>Capacity:</strong> <?= htmlspecialchars($room['capacity']) ?> person(s)</p>
                                    <p class="room-price"><strong>Price:</strong> RM<?= number_format($room['price'], 2) ?>/semester</p>
                                </div>
                                <?php 
                                // Parse features from database
                                $featuresList = [];
                                
                                if (!empty($room['features'])) {
                                    // Split features by comma if it's a string
                                    $featuresList = array_map('trim', explode(',', $room['features']));
                                }
                                
                                // If no features found, provide default based on room type
                                if (empty($featuresList)) {
                                    switch ($room['type']) {
                                        case 'Single':
                                            $featuresList = ['Study Desk', 'Wardrobe', 'Wi-Fi'];
                                            break;
                                        case 'Double':
                                            $featuresList = ['Study Desks (2)', 'Wardrobes (2)', 'Wi-Fi'];
                                            break;
                                        case 'Triple':
                                            $featuresList = ['Study Desks (3)', 'Wardrobes (3)', 'Wi-Fi'];
                                            break;
                                        default:
                                            $featuresList = ['Study Desk', 'Wardrobe', 'Wi-Fi'];
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
                                </div>                                <div class="room-actions">
                                    <!-- Edit Room Button -->
                                    <button type="button" 
                                            class="action-btn edit-room-btn" 
                                            title="Edit Room Status" 
                                            data-room-id="<?= $room['id'] ?>" 
                                            data-room-number="<?= htmlspecialchars($room['room_number']) ?>" 
                                            data-current-status="<?= htmlspecialchars($room['availability_status']) ?>" 
                                            onclick="editRoomStatus(this)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
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
    // Global function for editing room status - called directly from button onclick
    function editRoomStatus(button) {
        console.log('Edit button clicked', button);
        
        const roomId = button.getAttribute('data-room-id');
        const roomNumber = button.getAttribute('data-room-number');
        const currentStatus = button.getAttribute('data-current-status');
        
        console.log('Room data:', roomId, roomNumber, currentStatus);
          // Prompt for the new status - simplified to 3 options
        const newStatus = prompt(
            `Change status for Room ${roomNumber}\nCurrent status: ${currentStatus}\n\nEnter new status:\n- Available\n- Occupied\n- Under Maintenance`, 
            currentStatus
        );
        
        if (newStatus && ['Available', 'Occupied', 'Under Maintenance'].includes(newStatus)) {
            // Create form data
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('status', newStatus);
            
            // Show loading indicator
            const card = button.closest('.room-card');
            const statusElement = card.querySelector('.room-status');
            const originalContent = statusElement.innerHTML;
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            // Send AJAX request to update database
            fetch('update_room_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                    // Get the appropriate icon for the new status - simplified to 3 options
                    let statusIcon = 'fa-question-circle';
                    switch (newStatus) {
                        case 'Available': statusIcon = 'fa-check-circle'; break;
                        case 'Occupied': statusIcon = 'fa-user'; break;
                        case 'Under Maintenance': statusIcon = 'fa-tools'; break;
                    }
                      // Update UI on success
                    statusElement.innerHTML = `<i class="fas ${statusIcon}"></i> ${newStatus}`;
                    
                    // Update status class
                    const newClass = newStatus.toLowerCase().replace(/\s+/g, '-');
                    statusElement.className = `room-status status-${newClass}`;
                    
                    // Update dataset for filtering
                    card.dataset.status = newStatus;
                    
                    // Update the button's data attribute
                    button.setAttribute('data-current-status', newStatus);
                      // Update stat cards if stats were returned
                    if (data.stats) {
                        // Update the stat cards with new values
                        const statCards = document.querySelectorAll('.stat-item .stat-value');
                        if (statCards.length >= 4) {
                            // Function to update with animation
                            function updateStatWithAnimation(element, newValue) {
                                const oldValue = parseInt(element.textContent);
                                
                                // Only animate if value has changed
                                if (oldValue !== parseInt(newValue)) {
                                    // Update the value
                                    element.textContent = newValue;
                                    
                                    // Add highlight class
                                    element.classList.add('stat-updated');
                                    
                                    // Remove highlight class after animation completes
                                    setTimeout(() => {
                                        element.classList.remove('stat-updated');
                                    }, 1500);
                                }
                            }
                            
                            // Update each stat with animation
                            updateStatWithAnimation(statCards[0], data.stats.total_rooms);          // Total Rooms
                            updateStatWithAnimation(statCards[1], data.stats.available_rooms);      // Available
                            updateStatWithAnimation(statCards[2], data.stats.occupied_rooms);       // Occupied
                            updateStatWithAnimation(statCards[3], data.stats.maintenance_rooms);    // Maintenance
                        }
                    }
                    
                    // Show success notification
                    showNotification(`Room ${roomNumber} status updated to ${newStatus}`, 'success');
                } else {
                    // Restore original status on error
                    statusElement.innerHTML = originalContent;
                    showNotification('Failed to update room status: ' + data.message, 'error');
                    console.error('Status update error:', data.message);
                }
            })
            .catch(error => {
                // Restore original status on error
                statusElement.innerHTML = originalContent;
                showNotification('An error occurred while updating room status', 'error');
                console.error('Error:', error);
            });
        }
    }
    
    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
        // Room filtering functionality
        const roomTypeFilter = document.getElementById('roomTypeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const roomCards = document.querySelectorAll('.room-card');
        
        // Setup notification container
        const notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
        
        // Apply filters function
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
        
        // Add event listeners for filters
        roomTypeFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
    });
    
    // Function to show notifications
    function showNotification(message, type = 'success') {
        const notificationContainer = document.querySelector('.notification-container');
        if (!notificationContainer) return; // Safety check
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            </div>
            <div class="notification-message">${message}</div>
        `;
        notificationContainer.appendChild(notification);
        
        // Auto remove after a few seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
</script>

<?php require_once '../shared/includes/footer.php'; ?>
