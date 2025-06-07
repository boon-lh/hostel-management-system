<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once 'request_functions.php';

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Service Requests";
$additionalCSS = ["css/requests.css"];

// Get student ID from session
$username = $_SESSION["user"];
$studentId = 0;
$errors = [];
$success = "";
$roomId = 0;
$roomNumber = "";
$blockName = "";

// Get student ID from database
$stmt = $conn->prepare("SELECT s.id, r.id as room_id, r.room_number, hb.block_name
                       FROM students s
                       LEFT JOIN hostel_registrations sra ON s.id = sra.student_id AND sra.status = 'Checked In'
                       LEFT JOIN rooms r ON sra.room_id = r.id
                       LEFT JOIN hostel_blocks hb ON r.block_id = hb.id
                       WHERE s.username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentId = $row['id'];
    $roomId = $row['room_id'] ?? 0;
    $roomNumber = $row['room_number'] ?? 'Not assigned';
    $blockName = $row['block_name'] ?? 'Not assigned';
} else {
    $errors[] = "Student information not found.";
}

// Initialize arrays to hold requests
$requests = [];

// Process request submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'submit_request') {
        $request_type = $_POST['request_type'] ?? '';
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $preferred_date = $_POST['preferred_date'] ?? null;
        $preferred_time_slot = $_POST['preferred_time_slot'] ?? null;
        $new_room_id = isset($_POST['new_room_id']) ? $_POST['new_room_id'] : null;
        
        // Use the submitServiceRequest function
        $result = submitServiceRequest(
            $conn,
            $studentId,
            $request_type,
            $subject,
            $description,
            $roomId,
            $preferred_date,
            $preferred_time_slot,
            $new_room_id,
            isset($_FILES['attachment']) ? $_FILES['attachment'] : null
        );
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
        
    } elseif (isset($_POST['action']) && $_POST['action'] === 'cancel_request') {
        $request_id = $_POST['request_id'] ?? 0;
        
        // Use the cancelServiceRequest function
        $result = cancelServiceRequest($conn, $request_id, $studentId);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Fetch requests for the student using the function
if ($studentId > 0) {
    $requests = getStudentRequests($conn, $studentId);
}

// Fetch available rooms for room exchange if student has an assigned room
$available_rooms = [];
if ($roomId > 0) {
    $available_rooms = getAvailableRoomsForExchange($conn, $roomId);
}

// Include header
require_once '../shared/includes/header.php';

// Include student sidebar
require_once '../shared/includes/sidebar-student.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1><i class="fas fa-tools"></i> Service Requests</h1>
    </div>
    
    <div class="requests-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Submit New Request</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($roomId === 0 && $roomNumber === "Not assigned"): ?>
                            <div class="no-room-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>You currently do not have a room assignment. Some request types require room assignment.</p>
                            </div>
                        <?php else: ?>
                            <div class="room-info">
                                <p><strong>Your Room:</strong> <?php echo $blockName . ' - ' . $roomNumber; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form action="requests.php" method="POST" enctype="multipart/form-data" id="requestForm">
                            <input type="hidden" name="action" value="submit_request">
                            
                            <div class="form-group">
                                <label for="request_type">Request Type</label>
                                <select class="form-control" id="request_type" name="request_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="room_cleaning">Room Cleaning</option>
                                    <option value="internet_issue">Internet Issue</option>
                                    <option value="furniture">Furniture Request</option>
                                    <?php if ($roomId > 0): ?>
                                        <option value="room_exchange">Room Exchange</option>
                                        <option value="checkout">Check-out Request</option>
                                    <?php endif; ?>
                                    <option value="other">Other Request</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Brief subject of your request" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Please describe your request in detail" required></textarea>
                            </div>
                            
                            <!-- Date and Time fields - For maintenance and cleaning -->
                            <div id="dateTimeFields" style="display: none;">
                                <div class="form-group">
                                    <label for="preferred_date">Preferred Date</label>
                                    <?php $min_date = date('Y-m-d', strtotime('+1 day')); ?>
                                    <?php $max_date = date('Y-m-d', strtotime('+30 days')); ?>
                                    <input type="date" class="form-control" id="preferred_date" name="preferred_date" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>">
                                    <small class="form-text text-muted">Please select a date between tomorrow and 30 days from now</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="preferred_time_slot">Preferred Time Slot</label>
                                    <select class="form-control" id="preferred_time_slot" name="preferred_time_slot">
                                        <option value="">-- Select Time Slot --</option>
                                        <option value="09:00 AM - 11:00 AM">09:00 AM - 11:00 AM</option>
                                        <option value="11:00 AM - 01:00 PM">11:00 AM - 01:00 PM</option>
                                        <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM</option>
                                        <option value="04:00 PM - 06:00 PM">04:00 PM - 06:00 PM</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Room Exchange fields -->
                            <div id="roomExchangeFields" style="display: none;">
                                <div class="form-group">
                                    <label for="new_room_id">Preferred Room</label>
                                    <select class="form-control" id="new_room_id" name="new_room_id">
                                        <option value="">-- Select Room --</option>
                                        <?php foreach($available_rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>">
                                                <?php echo $room['block'] . ' - ' . $room['room_number']; ?> 
                                                (<?php echo $room['occupied']; ?>/<?php echo $room['capacity']; ?> occupied)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($available_rooms)): ?>
                                        <small class="form-text text-danger">No available rooms found for exchange</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachment">Attachment (optional)</label>
                                <input type="file" class="form-control-file" id="attachment" name="attachment">
                                <small class="form-text text-muted">You can attach a photo or document (JPG, PNG, GIF, PDF) up to 5MB.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block" id="submitButton">
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> My Requests</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="no-requests-message">
                                <i class="fas fa-info-circle"></i>
                                <p>You haven't submitted any service requests yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td>#<?php echo $request['id']; ?></td>
                                                <td>
                                                    <?php 
                                                    $type_badge = 'badge-info';
                                                    $type_icon = '';
                                                    switch ($request['request_type']) {
                                                        case 'maintenance':
                                                            $type_icon = '<i class="fas fa-wrench"></i> ';
                                                            break;
                                                        case 'checkout':
                                                            $type_icon = '<i class="fas fa-sign-out-alt"></i> ';
                                                            break;
                                                        case 'room_exchange':
                                                            $type_icon = '<i class="fas fa-exchange-alt"></i> ';
                                                            break;
                                                        case 'room_cleaning':
                                                            $type_icon = '<i class="fas fa-broom"></i> ';
                                                            break;
                                                        case 'internet_issue':
                                                            $type_icon = '<i class="fas fa-wifi"></i> ';
                                                            break;
                                                        case 'furniture':
                                                            $type_icon = '<i class="fas fa-couch"></i> ';
                                                            break;
                                                        default:
                                                            $type_icon = '<i class="fas fa-question-circle"></i> ';
                                                    }
                                                    echo '<span class="badge ' . $type_badge . '">' . $type_icon . ucwords(str_replace('_', ' ', $request['request_type'])) . '</span>'; 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['subject']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = 'badge-info';
                                                    $status_icon = '<i class="fas fa-clock"></i> ';
                                                    
                                                    switch ($request['status']) {
                                                        case 'pending':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'approved':
                                                            $status_class = 'badge-info';
                                                            $status_icon = '<i class="fas fa-thumbs-up"></i> ';
                                                            break;
                                                        case 'in_progress':
                                                            $status_class = 'badge-primary';
                                                            $status_icon = '<i class="fas fa-spinner fa-spin"></i> ';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'badge-success';
                                                            $status_icon = '<i class="fas fa-check-circle"></i> ';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'badge-danger';
                                                            $status_icon = '<i class="fas fa-times-circle"></i> ';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'badge-secondary';
                                                            $status_icon = '<i class="fas fa-ban"></i> ';
                                                            break;
                                                    }
                                                    
                                                    echo '<span class="badge ' . $status_class . '">' . $status_icon . ucfirst(str_replace('_', ' ', $request['status'])) . '</span>';
                                                    ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewRequest(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    
                                                    <?php if ($request['status'] === 'pending' || $request['status'] === 'approved'): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="showCancelModal(<?php echo $request['id']; ?>)">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request View Modal -->
<div class="modal" id="requestModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-tools"></i> Request Details</h4>
                <button type="button" class="close" onclick="closeRequestModal()">&times;</button>
            </div>
            <div class="modal-body" id="requestContent">
                <!-- Request content will be dynamically inserted here -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRequestModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Request Modal -->
<div class="modal" id="cancelModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Cancel Request</h4>
                <button type="button" class="close" onclick="closeCancelModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="cancelForm" method="POST" action="requests.php">
                    <input type="hidden" name="action" value="cancel_request">
                    <input type="hidden" name="request_id" id="cancel_request_id" value="">
                    
                    <div class="warning-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Are you sure you want to cancel this request? This action cannot be undone.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">No, Keep Request</button>
                <button type="button" class="btn btn-danger" onclick="submitCancelForm()"><i class="fas fa-times"></i> Yes, Cancel Request</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide fields based on request type
document.addEventListener('DOMContentLoaded', function() {
    const requestType = document.getElementById('request_type');
    const dateTimeFields = document.getElementById('dateTimeFields');
    const roomExchangeFields = document.getElementById('roomExchangeFields');
    const preferredDate = document.getElementById('preferred_date');
    const preferredTimeSlot = document.getElementById('preferred_time_slot');
    const newRoomId = document.getElementById('new_room_id');
    
    requestType.addEventListener('change', function() {
        // Reset required attributes
        preferredDate.required = false;
        preferredTimeSlot.required = false;
        newRoomId.required = false;
        
        // Hide all conditional fields first
        dateTimeFields.style.display = 'none';
        roomExchangeFields.style.display = 'none';
        
        // Show relevant fields based on selection
        switch(this.value) {
            case 'maintenance':
            case 'room_cleaning':
                dateTimeFields.style.display = 'block';
                preferredDate.required = true;
                preferredTimeSlot.required = true;
                break;
            case 'room_exchange':
                roomExchangeFields.style.display = 'block';
                newRoomId.required = true;
                break;
        }
    });
});

// View Request Modal Functions
function viewRequest(requestId) {
    document.getElementById('requestContent').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('requestModal').style.display = 'block';
    
    // Fetch request details using AJAX
    fetch('get_request.php?id=' + requestId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('requestContent').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
            } else {
                displayRequestDetails(data);
            }
        })
        .catch(error => {
            document.getElementById('requestContent').innerHTML = '<div class="alert alert-danger">Error loading request details: ' + error.message + '</div>';
        });
}

function displayRequestDetails(request) {
    // Format dates
    const createdDate = new Date(request.created_at).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    const updatedDate = new Date(request.updated_at).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    
    let preferredDateHTML = '';
    if (request.preferred_date) {
        const preferredDate = new Date(request.preferred_date).toLocaleDateString('en-US', { 
            year: 'numeric', month: 'long', day: 'numeric'
        });
        preferredDateHTML = `
            <div class="preferred-datetime">
                <p><strong>Preferred Date:</strong> ${preferredDate}</p>
                <p><strong>Preferred Time Slot:</strong> ${request.preferred_time_slot || 'Not specified'}</p>
            </div>
        `;
    }
    
    // Status badge
    let statusClass = 'badge-info';
    let statusIcon = '<i class="fas fa-clock"></i> ';
    switch (request.status) {
        case 'pending':
            statusClass = 'badge-warning';
            break;
        case 'approved':
            statusClass = 'badge-info';
            statusIcon = '<i class="fas fa-thumbs-up"></i> ';
            break;
        case 'in_progress':
            statusClass = 'badge-primary';
            statusIcon = '<i class="fas fa-spinner fa-spin"></i> ';
            break;
        case 'completed':
            statusClass = 'badge-success';
            statusIcon = '<i class="fas fa-check-circle"></i> ';
            break;
        case 'rejected':
            statusClass = 'badge-danger';
            statusIcon = '<i class="fas fa-times-circle"></i> ';
            break;
        case 'cancelled':
            statusClass = 'badge-secondary';
            statusIcon = '<i class="fas fa-ban"></i> ';
            break;
    }
    
    // Format request type
    const requestType = request.request_type.replace(/_/g, ' ');
    
    // Room information
    let roomInfo = '';
    if (request.room_number && request.block) {
        roomInfo = `
            <p><strong>Room:</strong> ${request.block} - ${request.room_number}</p>
        `;
    }
    
    // New room information (for room exchange)
    let newRoomInfo = '';
    if (request.request_type === 'room_exchange' && request.new_room_data) {
        newRoomInfo = `
            <div class="room-exchange-details">
                <h5><i class="fas fa-exchange-alt"></i> Room Exchange Details</h5>
                <p><strong>Current Room:</strong> ${request.block} - ${request.room_number}</p>
                <p><strong>Requested Room:</strong> ${request.new_room_data.block} - ${request.new_room_data.room_number}</p>
            </div>
        `;
    }
    
    // Attachment link
    let attachmentHTML = '';
    if (request.attachment_path) {
        attachmentHTML = `
            <div class="attachment-section">
                <h5><i class="fas fa-paperclip"></i> Attachment</h5>
                <div class="attachment-preview">
                    <a href="../${request.attachment_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i> View Attachment
                    </a>
                </div>
            </div>
        `;
    }
    
    // Notes section
    let notesHTML = '';
    if (request.notes) {
        notesHTML = `
            <div class="notes-section">
                <h5><i class="fas fa-sticky-note"></i> Notes from Staff</h5>
                <div class="notes-box">
                    ${request.notes.replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
    }
    
    // Rejection reason
    let rejectionHTML = '';
    if (request.status === 'rejected' && request.rejection_reason) {
        rejectionHTML = `
            <div class="rejection-section">
                <h5><i class="fas fa-times-circle"></i> Reason for Rejection</h5>
                <div class="rejection-box">
                    ${request.rejection_reason.replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
    }
    
    // History section
    let historyHTML = '';
    if (request.history && request.history.length > 0) {
        let historyItems = '';
        request.history.forEach(item => {
            const historyDate = new Date(item.created_at).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
            });
            
            let statusIconHistory = '<i class="fas fa-clock"></i> ';
            switch (item.status) {
                case 'approved':
                    statusIconHistory = '<i class="fas fa-thumbs-up"></i> ';
                    break;
                case 'in_progress':
                    statusIconHistory = '<i class="fas fa-spinner"></i> ';
                    break;
                case 'completed':
                    statusIconHistory = '<i class="fas fa-check-circle"></i> ';
                    break;
                case 'rejected':
                    statusIconHistory = '<i class="fas fa-times-circle"></i> ';
                    break;
                case 'cancelled':
                    statusIconHistory = '<i class="fas fa-ban"></i> ';
                    break;
            }
            
            historyItems += `
                <div class="timeline-item">
                    <div class="timeline-marker ${item.status}">
                        ${statusIconHistory}
                    </div>
                    <div class="timeline-content">
                        <h6>Status changed to ${item.status.replace(/_/g, ' ')}</h6>
                        <p class="timeline-date">${historyDate}</p>
                        ${item.comments ? `<p class="timeline-comment">${item.comments}</p>` : ''}
                    </div>
                </div>
            `;
        });
        
        historyHTML = `
            <div class="history-section">
                <h5><i class="fas fa-history"></i> Status History</h5>
                <div class="timeline">
                    ${historyItems}
                </div>
            </div>
        `;
    }
    
    // Build the complete HTML
    const requestHTML = `
        <div class="request-details">
            <h3>${request.subject}</h3>
            
            <div class="request-meta">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Request ID:</strong> #${request.id}</p>
                        <p><strong>Type:</strong> ${requestType.charAt(0).toUpperCase() + requestType.slice(1)}</p>
                        ${roomInfo}
                        <p><strong>Submitted:</strong> ${createdDate}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge ${statusClass}">${statusIcon}${request.status.replace(/_/g, ' ')}</span></p>
                        <p><strong>Last Updated:</strong> ${updatedDate}</p>
                    </div>
                </div>
            </div>
            
            ${preferredDateHTML}
            ${newRoomInfo}
            
            <div class="request-description">
                <h5><i class="fas fa-align-left"></i> Description</h5>
                <div class="description-box">
                    ${request.description.replace(/\n/g, '<br>')}
                </div>
            </div>
            
            ${attachmentHTML}
            ${notesHTML}
            ${rejectionHTML}
            ${historyHTML}
        </div>
    `;
    
    document.getElementById('requestContent').innerHTML = requestHTML;
}

function closeRequestModal() {
    document.getElementById('requestModal').style.display = 'none';
}

// Cancel Request Modal Functions
function showCancelModal(requestId) {
    document.getElementById('cancel_request_id').value = requestId;
    document.getElementById('cancelModal').style.display = 'block';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

function submitCancelForm() {
    document.getElementById('cancelForm').submit();
}
</script>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>
