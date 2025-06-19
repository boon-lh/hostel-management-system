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
$additionalCSS = ["css/block_rooms.css", "css/dashboard.css", "css/room_management.css", "css/room_table.css"];

// Get rooms data from database
$rooms = [];

// Get filter parameters
$roomTypeFilter = isset($_GET['room_type']) ? $_GET['room_type'] : 'all';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build WHERE clause for filters
$whereClause = "block_id = ?";
$params = array($block_id);
$types = "i";

if ($roomTypeFilter !== 'all') {
    $whereClause .= " AND type = ?";
    $params[] = $roomTypeFilter;
    $types .= "s";
}

if ($statusFilter !== 'all') {
    $whereClause .= " AND availability_status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Query to get rooms for this block with filters
$roomsQuery = "SELECT * FROM rooms WHERE $whereClause ORDER BY room_number";
$roomsStmt = $conn->prepare($roomsQuery);

if ($roomsStmt) {
    $roomsStmt->bind_param($types, ...$params);
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
    <div class="card">        <div class="card-header">
            <div class="card-title-area">
                <div class="card-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <h2 class="card-title">Rooms</h2>
            </div>            <div class="card-actions">
                <button type="button" id="addRoomBtn">
                    <i class="fas fa-plus"></i> Add Room
                </button>
                <div class="filter-container">
                    <select id="roomTypeFilter" class="filter-select">
                        <option value="all">All Room Types</option>
                        <option value="Single">Single</option>
                        <option value="Double">Double</option>
                        <option value="Triple">Triple</option>
                        <option value="Quad">Quad</option>
                    </select>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Statuses</option>
                        <option value="Available">Available</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">            <?php if (empty($rooms)): ?>
                <div class="no-data-container">
                    <div class="no-data-icon">
                        <i class="fas fa-door-closed"></i>
                    </div>
                    <h3>No Rooms Found</h3>
                    <p>There are no rooms assigned to this block yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="rooms-table">                        <thead>
                            <tr>
                                <th class="room-number-col">Room No.</th>
                                <th class="room-type-col">Type</th>
                                <th class="capacity-col">Capacity</th>
                                <th class="price-col">Price/Semester</th>
                                <th class="status-col">Status</th>
                                <th class="features-col">Features</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>                            <tr class="room-row" data-room-type="<?= htmlspecialchars($room['type']) ?>" data-status="<?= htmlspecialchars($room['availability_status']) ?>">                                <!-- Room Number -->
                                <td class="room-number-col"><span class="room-number"><?= htmlspecialchars($room['room_number']) ?></span></td>
                                
                                <!-- Room Type -->
                                <td class="room-type-col"><span class="room-type"><?= htmlspecialchars($room['type']) ?></span></td>
                                
                                <!-- Capacity -->
                                <td class="capacity-col"><span class="room-capacity"><?= htmlspecialchars($room['capacity']) ?> person(s)</span></td>
                                
                                <!-- Price -->
                                <td class="price-col"><span class="room-price">RM<?= number_format($room['price'], 2) ?></span></td>
                                
                                <!-- Status -->
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
                                ?>                                <td class="status-col">
                                    <span class="status-pill <?= $statusClass ?>">
                                        <i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars($room['availability_status']) ?>
                                    </span>
                                </td>
                                
                                <!-- Features -->
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
                                ?>                                <td class="features-col">
                                    <div class="room-features-list">
                                        <?php 
                                        // Limit the number of features displayed to prevent overflow
                                        $limitedFeatures = array_slice($featuresList, 0, 4);
                                        $moreCount = count($featuresList) - count($limitedFeatures);

                                        // Display features as tags
                                        foreach ($limitedFeatures as $feature) {
                                            echo '<span class="feature-tag">' . htmlspecialchars($feature) . '</span>';
                                        }
                                        if ($moreCount > 0) {
                                            echo '<span class="more-features" title="' . implode(", ", array_map('htmlspecialchars', array_slice($featuresList, 4))) . '">+' . $moreCount . ' more</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                  <!-- Actions -->                                <td class="actions-col">
                                    <div class="action-buttons">
                                        <button type="button" 
                                                class="action-btn edit-btn" 
                                                title="Edit Room Status" 
                                                data-room-id="<?= $room['id'] ?>" 
                                                data-room-number="<?= htmlspecialchars($room['room_number']) ?>" 
                                                data-current-status="<?= htmlspecialchars($room['availability_status']) ?>" 
                                                onclick="editRoomStatus(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                                class="action-btn delete-btn" 
                                                title="Delete Room" 
                                                data-room-id="<?= $room['id'] ?>" 
                                                data-room-number="<?= htmlspecialchars($room['room_number']) ?>" 
                                                onclick="deleteRoom(this)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>                            <?php endforeach; ?>
                        </tbody>
                    </table>                </div>
                <div class="table-pagination-container">
                    <div class="table-info"></div>
                    <div class="pagination-container">
                        <div class="pagination" id="roomsPagination">
                            <!-- Pagination links will be added here via JavaScript -->
                        </div>
                        <div class="pagination-info" id="paginationInfo">
                            <!-- Pagination info will be added here via JavaScript -->
                        </div>
                    </div>
                </div>
                    Total: <?= count($rooms) ?> rooms
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div id="addRoomModal" class="modal">
    <div class="modal-content" onclick="event.stopPropagation();">
        <div class="modal-header">
            <h3>Add New Room</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addRoomForm">
                <input type="hidden" name="block_id" value="<?= $block_id ?>">
                
                <div class="form-group">
                    <label for="roomNumber">Room Number <span class="required">*</span></label>
                    <input type="text" id="roomNumber" name="room_number" required placeholder="e.g., A101">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="roomType">Room Type <span class="required">*</span></label>
                        <select id="roomType" name="room_type" required onchange="updateCapacity()">
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Quad">Quad</option>
                        </select>
                    </div>
                    <div class="form-group half">
                        <label for="capacity">Capacity <span class="required">*</span></label>
                        <input type="number" id="capacity" name="capacity" min="1" max="4" value="1" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="price">Price (RM/semester) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" min="0" step="0.01" required placeholder="e.g., 1200.00">
                    </div>
                    <div class="form-group half">
                        <label for="status">Initial Status</label>
                        <select id="status" name="status">
                            <option value="Available">Available</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="features">Features (comma separated)</label>
                    <input type="text" id="features" name="features" placeholder="e.g., Study Desk, Wardrobe, Wi-Fi">
                    <small>Common features will be added automatically based on room type</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn secondary-btn" id="cancelAddRoomBtn">Cancel</button>
                    <button type="submit" class="btn primary-btn">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inline CSS fix for modal display -->
<style>
    /* Override modal display styles for compatibility */
    #addRoomModal {
        display: none;
        z-index: 9999 !important;
    }
    
    #addRoomModal.show {
        display: block !important;
        opacity: 1 !important;
    }
    
    /* Ensure modal content is visible and properly styled */
    .modal-content {
        max-height: 90vh;
        max-width: 600px;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }
    
    /* Form elements styling */
    #addRoomForm .form-group {
        margin-bottom: 15px;
    }
    
    /* Ensure buttons are visible and properly styled */
    .btn.primary-btn, .btn.secondary-btn {
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn.primary-btn {
        background-color: #4CAF50;
        color: white;
    }
    
    .btn.secondary-btn {
        background-color: #f1f1f1;
        color: #333;
    }
    
    .btn.primary-btn:hover {
        background-color: #45a049;
    }
</style>

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
            formData.append('status', newStatus);            // Show loading indicator - updated for table view
            const row = button.closest('tr');
            console.log('Found row:', row); 
            
            // Check if the row has the expected classes
            if (row && !row.classList.contains('room-row')) {
                console.warn('Row does not have room-row class, adding it');
                row.classList.add('room-row');
            }
            
            const statusCell = row.querySelector('td.status-col');
            console.log('Found status cell:', statusCell);
            
            const statusElement = statusCell ? statusCell.querySelector('.status-pill') : null;
            console.log('Found status element:', statusElement);
            
            if (!statusElement) {
                console.error('Status element not found');
                showNotification('Error: Could not find status element', 'error');
                return;
            }
            
            const originalContent = statusElement.innerHTML;
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
              // Debug the form data before sending
            console.log('Sending data to update:', {
                room_id: roomId,
                new_status: newStatus
            });
              // Send AJAX request to update database
            fetch('update_room_status.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // Include credentials (session cookies)
                cache: 'no-cache' // Avoid caching
            })            .then(response => {
                console.log('Response status:', response.status);
                // Check if the response is ok (status in the range 200-299)
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Server responded with status ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {                    // Get the appropriate icon for the new status - simplified to 3 options
                    let statusIcon = 'fa-question-circle';
                    switch (newStatus) {
                        case 'Available': statusIcon = 'fa-check-circle'; break;
                        case 'Occupied': statusIcon = 'fa-user'; break;
                        case 'Under Maintenance': statusIcon = 'fa-tools'; break;
                    }                    // Update UI on success
                    const newClass = newStatus.toLowerCase().replace(/\s+/g, '-');
                    
                    // Debug the elements we're trying to update
                    console.log('Status element:', statusElement);
                    console.log('Row element:', row);
                    
                    // Set new status content and class
                    statusElement.innerHTML = `<i class="fas ${statusIcon}"></i> ${newStatus}`;
                    statusElement.className = `status-pill status-${newClass}`;
                    
                    // Update dataset for filtering
                    row.dataset.status = newStatus;
                    
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
                    
                    // Check database state after update for debugging
                    setTimeout(() => {
                        checkRoomStatus(roomId);
                    }, 1000);
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
        
        // Reapply filters and pagination after status update
        if (typeof applyFiltersAndPaginate === 'function') {
            applyFiltersAndPaginate();
        }
    }
    
    // Debugging function to check room status in the database
    function checkRoomStatus(roomId) {
        fetch(`check_room_status.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Database room status check:', data);
            })
            .catch(error => {
                console.error('Error checking room status:', error);
            });
    }
    
    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
        // Room filtering functionality
        const roomTypeFilter = document.getElementById('roomTypeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const roomRows = document.querySelectorAll('.room-row');
        
        // Setup notification container
        const notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);        // Apply filters function
        function applyFilters() {
            console.log('Applying filters...');
            const selectedRoomType = roomTypeFilter.value;
            const selectedStatus = statusFilter.value;
            
            console.log('Filters:', selectedRoomType, selectedStatus);
            
            // Save filters to URL params
            const currentUrl = new URL(window.location.href);
            if (selectedRoomType !== 'all') {
                currentUrl.searchParams.set('room_type', selectedRoomType);
            } else {
                currentUrl.searchParams.delete('room_type');
            }
            
            if (selectedStatus !== 'all') {
                currentUrl.searchParams.set('status', selectedStatus);
            } else {
                currentUrl.searchParams.delete('status');
            }
            
            // Update browser history without reloading the page
            window.history.replaceState({}, '', currentUrl.toString());
            
            // Get all room rows (tr elements) from the table
            const roomRows = document.querySelectorAll('.room-row');
            console.log('Total room rows:', roomRows.length);
            
            let visibleCount = 0;
            
            // Filter rows based on selected filters
            roomRows.forEach(row => {
                const roomType = row.dataset.roomType;
                const status = row.dataset.status;
                
                const roomTypeMatch = selectedRoomType === 'all' || roomType === selectedRoomType;
                const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                
                if (roomTypeMatch && statusMatch) {
                    // This row matches the filter
                    row.dataset.filtered = 'visible';
                    visibleCount++;
                } else {
                    // This row doesn't match the filter - hide it completely
                    row.dataset.filtered = 'hidden';
                    row.classList.add('pagination-hidden');
                }
            });
            
            console.log('Visible rows after filtering:', visibleCount);
            
            // Update table info to reflect filtered count
            updateTableInfo();
        }
          // Pagination variables
        let currentPage = 1;
        const rowsPerPage = 10;
          // Function to update the table info showing visible rows count
        function updateTableInfo() {
            const filteredVisibleRows = document.querySelectorAll('.room-row[data-filtered="visible"]').length;
            const totalRows = document.querySelectorAll('.room-row').length;
            
            const tableInfo = document.querySelector('.table-info');
            if (tableInfo) {
                tableInfo.textContent = `Showing ${filteredVisibleRows} of ${totalRows} rooms`;
                console.log('Updated table info:', filteredVisibleRows, 'of', totalRows);
            }
        }
          // Function to paginate visible rows
        function paginateRows() {
            console.log('Paginating rows...');
            // Get all rows that are not hidden by filtering
            const visibleRows = Array.from(document.querySelectorAll('.room-row')).filter(row => {
                return row.dataset.filtered !== 'hidden';
            });
            
            console.log('Total visible rows after filtering:', visibleRows.length);
            const totalVisibleRows = visibleRows.length;
            
            // Calculate total pages needed
            const totalPages = Math.ceil(totalVisibleRows / rowsPerPage);
            console.log('Total pages needed:', totalPages);
            
            // Adjust current page if it's out of bounds
            if (currentPage > totalPages) {
                currentPage = totalPages > 0 ? totalPages : 1;
            }
            
            console.log('Current page:', currentPage);
              // First hide all rows that match the filter
            visibleRows.forEach(row => {
                // Hide for pagination purposes
                row.classList.add('pagination-hidden');
                // But make sure we keep the filtered state
                if (row.dataset.filtered === 'visible') {
                    row.style.display = 'none'; // Initially hide even filtered rows
                }
            });
            
            // Show only rows for the current page
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, totalVisibleRows);
            
            console.log('Showing rows from', startIndex, 'to', endIndex - 1);
              for (let i = startIndex; i < endIndex; i++) {
                if (visibleRows[i]) {
                    visibleRows[i].classList.remove('pagination-hidden');
                    visibleRows[i].style.display = ''; // Make visible
                    console.log('Showing row:', visibleRows[i].querySelector('.room-number-col').textContent.trim());
                }
            }
            
            // Count actually visible rows
            const actuallyVisibleRows = document.querySelectorAll('.room-row:not(.pagination-hidden):not([data-filtered="hidden"])').length;
            console.log('Actually visible rows after pagination:', actuallyVisibleRows);
            
            // Update pagination info
            const paginationInfo = document.getElementById('paginationInfo');
            if (paginationInfo) {
                paginationInfo.textContent = `Page ${currentPage} of ${totalPages > 0 ? totalPages : 1}`;
                console.log('Updated pagination info');
            }
            
            // Update pagination links
            updatePaginationLinks(totalPages);
        }
        
        // Function to update pagination links
        function updatePaginationLinks(totalPages) {
            const pagination = document.getElementById('roomsPagination');
            if (!pagination) return;
            
            pagination.innerHTML = '';
            
            // Add Previous button
            const prevBtn = document.createElement('a');
            prevBtn.classList.add('pagination-link');
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevBtn.title = 'Previous Page';
            
            if (currentPage === 1) {
                prevBtn.classList.add('disabled');
            } else {
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        paginateRows();
                    }
                });
            }
            pagination.appendChild(prevBtn);
            
            // Determine which page numbers to show
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            // Adjust if we're near the end
            if (endPage - startPage < 4 && startPage > 1) {
                startPage = Math.max(1, endPage - 4);
            }
            
            // Add first page if not included in the range
            if (startPage > 1) {
                const firstPage = document.createElement('a');
                firstPage.classList.add('pagination-link');
                firstPage.textContent = '1';
                firstPage.addEventListener('click', () => {
                    currentPage = 1;
                    paginateRows();
                });
                pagination.appendChild(firstPage);
                
                // Add ellipsis if needed
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.classList.add('pagination-link', 'disabled');
                    ellipsis.textContent = '...';
                    pagination.appendChild(ellipsis);
                }
            }
            
            // Add page numbers
            for (let i = startPage; i <= endPage; i++) {
                const pageLink = document.createElement('a');
                pageLink.classList.add('pagination-link');
                if (i === currentPage) {
                    pageLink.classList.add('active');
                }
                pageLink.textContent = i.toString();
                pageLink.addEventListener('click', () => {
                    currentPage = i;
                    paginateRows();
                });
                pagination.appendChild(pageLink);
            }
            
            // Add ellipsis and last page if needed
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.classList.add('pagination-link', 'disabled');
                    ellipsis.textContent = '...';
                    pagination.appendChild(ellipsis);
                }
                
                const lastPage = document.createElement('a');
                lastPage.classList.add('pagination-link');
                lastPage.textContent = totalPages.toString();
                lastPage.addEventListener('click', () => {
                    currentPage = totalPages;
                    paginateRows();
                });
                pagination.appendChild(lastPage);
            }
            
            // Add Next button
            const nextBtn = document.createElement('a');
            nextBtn.classList.add('pagination-link');
            nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextBtn.title = 'Next Page';
            
            if (currentPage === totalPages || totalPages === 0) {
                nextBtn.classList.add('disabled');
            } else {
                nextBtn.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage++;
                        paginateRows();
                    }
                });
            }
            pagination.appendChild(nextBtn);
        }
        
        // Apply filters and pagination together
        function applyFiltersAndPaginate() {
            // Reset to first page when filters change
            currentPage = 1;
            
            // Apply filters first
            applyFilters();
            
            // Then apply pagination
            paginateRows();
        }
          // Add event listeners for filters
        roomTypeFilter.addEventListener('change', applyFiltersAndPaginate);
        statusFilter.addEventListener('change', applyFiltersAndPaginate);
        
        // Initialize the table info and pagination when page loads
        console.log('Initializing pagination...');
        // First, mark all rows as visible for initial pagination
        document.querySelectorAll('.room-row').forEach(row => {
            row.dataset.filtered = 'visible';
        });
        updateTableInfo();
        paginateRows();

        // Debug
        console.log('Pagination setup complete');
    });
    
    // Add Room form submission - outside of the previous event listener context
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded for room form');
        const addRoomForm = document.getElementById('addRoomForm');
        console.log('Form element found:', addRoomForm);
        
        if (addRoomForm) {
            addRoomForm.addEventListener('submit', function(event) {
                console.log('Form submit event triggered');
                event.preventDefault(); // Prevent default form submission
                
                // Gather form data
                const formData = new FormData(addRoomForm);
                
                // Show loading indicator on the submit button
                const submitButton = addRoomForm.querySelector('.btn.primary-btn');
                const originalButtonText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                submitButton.disabled = true;
                
                // Send AJAX request to add new room
                fetch('add_room.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Server response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    // Restore button text and state
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                    
                    if (data.success) {
                        // Close the modal
                        hideAddRoomModal();
                        
                        // Optionally, refresh the room list or add the new room to the list
                        location.reload(); // Simple way to refresh the page
                        
                        // Show success notification
                        showNotification('New room added successfully', 'success');
                    } else {
                        // Show error notification
                        showNotification('Failed to add room: ' + data.message, 'error');
                        console.error('Add room error:', data.message);
                    }
                })                .catch(error => {
                    // Restore button text and state
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                    
                    // Show detailed error notification
                    const errorMessage = 'Error: ' + (error.message || 'Unknown error');
                    showNotification(errorMessage, 'error');
                    console.error('Error details:', error);
                    
                    // Display error in a more visible way for debugging
                    alert('Error occurred: ' + errorMessage + '\nCheck console for details.');
                });
            });
        }
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
      // Show Add Room modal
    function showAddRoomModal() {
        console.log('Show modal function called');
        const modal = document.getElementById('addRoomModal');
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'block'; // Adding direct style for compatibility
            
            // Optional: Reset the form
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                
                // Set default values if needed
                const roomTypeSelect = form.querySelector('select[name="room_type"]');
                if (roomTypeSelect) {
                    roomTypeSelect.value = 'Single'; // Default to Single
                }
                
                const statusSelect = form.querySelector('select[name="status"]');
                if (statusSelect) {
                    statusSelect.value = 'Available'; // Default to Available
                }
            }
        } else {
            console.error('Modal element not found');
        }
    }
    
    // Hide Add Room modal
    function hideAddRoomModal() {
        console.log('Hide modal function called');
        const modal = document.getElementById('addRoomModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none'; // Adding direct style for compatibility
        } else {
            console.error('Modal element not found');
        }
    }
    
    // Update capacity input based on room type selection
    function updateCapacity() {
        const roomTypeSelect = document.getElementById('roomType');
        const capacityInput = document.getElementById('capacity');
        
        if (roomTypeSelect && capacityInput) {
            const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
            const maxCapacity = selectedOption.getAttribute('data-capacity');
            
            // Update the max attribute of the capacity input
            capacityInput.setAttribute('max', maxCapacity);
            
            // Optionally, adjust the current value if it exceeds the new max
            if (parseInt(capacityInput.value) > parseInt(maxCapacity)) {
                capacityInput.value = maxCapacity;
            }
        }
    }
    
    // Make sure the Add Room button works correctly
    document.addEventListener('DOMContentLoaded', function() {
        const addRoomBtn = document.getElementById('addRoomBtn');
        if (addRoomBtn) {
            console.log('Add Room button found, adding direct event listener');
            addRoomBtn.addEventListener('click', function() {
                console.log('Add Room button clicked');
                showAddRoomModal();
            });
        } else {
            console.error('Add Room button not found');
        }

        // Add event listener to close modal button as well
        const closeModalButtons = document.querySelectorAll('.close-modal');
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Close modal button clicked');
                hideAddRoomModal();
            });
        });

        // Add event listener to close the modal when clicking outside it
        document.getElementById('addRoomModal').addEventListener('click', function(event) {
            if (event.target === this) {
                console.log('Clicked outside modal content');
                hideAddRoomModal();
            }
        });

        // Add event listener for the cancel button
        const cancelAddRoomBtn = document.getElementById('cancelAddRoomBtn');
        if (cancelAddRoomBtn) {
            cancelAddRoomBtn.addEventListener('click', function() {
                console.log('Cancel button clicked');
                hideAddRoomModal();
            });
        }
    });
    
    // Initialize tooltips for features
    document.addEventListener('mouseover', function(e) {
        if (e.target && e.target.classList.contains('more-features')) {
            const title = e.target.getAttribute('title');
            if (title) {
                // Create and show tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = title;
                document.body.appendChild(tooltip);
                
                // Position tooltip
                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.bottom + 10 + 'px';
                
                // Store tooltip reference
                e.target.tooltip = tooltip;
                
                // Remove title to prevent default tooltip
                e.target.setAttribute('data-original-title', title);
                e.target.removeAttribute('title');
            }
        }
    });
    
    document.addEventListener('mouseout', function(e) {
        if (e.target && e.target.classList.contains('more-features') && e.target.tooltip) {
            // Remove tooltip
            document.body.removeChild(e.target.tooltip);
            e.target.tooltip = null;
            
            // Restore title
            const originalTitle = e.target.getAttribute('data-original-title');
            if (originalTitle) {
                e.target.setAttribute('title', originalTitle);
                e.target.removeAttribute('data-original-title');
            }
        }
    });
    
    // Global function for deleting a room - called directly from button onclick
    function deleteRoom(button) {
        console.log('Delete button clicked', button);
        
        const roomId = button.getAttribute('data-room-id');
        const roomNumber = button.getAttribute('data-room-number');
        
        console.log('Room data for deletion:', roomId, roomNumber);
        
        // Confirm deletion with the admin
        if (!confirm(`Are you sure you want to delete Room ${roomNumber}?\n\nThis action cannot be undone. All room data will be permanently removed.`)) {
            return; // User canceled the action
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('room_id', roomId);
        
        // Get the row for later removal
        const row = button.closest('tr');
        
        // Show loading state on the button
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
          // Debug before sending
        console.log('About to send delete request for room ID:', roomId);
        
        // Send AJAX request to delete room
        fetch('delete_room.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            cache: 'no-cache'
        })
        .then(response => {
            console.log('Delete response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server responded with status ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response for delete:', data);
            
            if (data.success) {
                // Animate the row removal
                row.style.transition = 'all 0.5s ease';
                row.style.backgroundColor = '#ffdddd';
                row.style.opacity = '0';
                
                setTimeout(() => {
                    // Remove the row from DOM after animation
                    row.remove();
                    
                    // Update stats if provided
                    if (data.stats) {
                        const statCards = document.querySelectorAll('.stat-item .stat-value');
                        if (statCards.length >= 4) {
                            // Update total rooms count
                            statCards[0].textContent = data.stats.total_rooms;
                            // Update available rooms count
                            statCards[1].textContent = data.stats.available_rooms;
                            // Update occupied rooms count
                            statCards[2].textContent = data.stats.occupied_rooms;
                            // Update maintenance rooms count
                            statCards[3].textContent = data.stats.maintenance_rooms;
                        }
                    }
                    
                    // Update table info to reflect count changes
                    if (typeof updateTableInfo === 'function') {
                        updateTableInfo();
                    }
                    
                    // Reapply filters and pagination after deletion
                    if (typeof applyFiltersAndPaginate === 'function') {
                        applyFiltersAndPaginate();
                    }
                    
                    // Show success notification
                    showNotification(`Room ${roomNumber} has been deleted`, 'success');
                }, 500);
            } else {
                // Reset button state
                button.disabled = false;
                button.innerHTML = originalHTML;
                
                // Show error notification
                showNotification(data.message || 'Failed to delete room', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting room:', error);
            
            // Reset button state
            button.disabled = false;
            button.innerHTML = originalHTML;
              // Show detailed error notification with fallback option
            const errorMsg = error.message || 'An error occurred while deleting the room';
            showNotification('Error: ' + errorMsg, 'error');
            
            // Log additional details for debugging
            console.error('Delete request failed for room ' + roomNumber + ' (ID: ' + roomId + ')');
            
            // Offer fallback option after a short delay
            setTimeout(() => {
                if (confirm('The delete request failed. Would you like to try an alternative method?')) {
                    window.location.href = 'simple_delete_room.php?id=' + roomId;
                }
            }, 1500);
        });
    }
</script>

<?php require_once '../shared/includes/footer.php'; ?>            <!-- Direct update form link for administrative use -->
            <div style="margin: 10px 0; font-size: 12px; color: #666;">
                <a href="direct_update_room.php">Direct Room Update Form</a>
            </div>
