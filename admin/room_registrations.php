<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\room_registrations.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Define additional CSS
$pageTitle = "Room Registration Requests | MMU Hostel Management";
$additionalCSS = ["css/room_registrations.css"];

require_once "../shared/includes/db_connection.php";

// Check if we have an action and id from URL (from dashboard)
if (isset($_GET['action']) && isset($_GET['id']) && ($_GET['action'] === 'approve' || $_GET['action'] === 'reject')) {
    $registration_id = intval($_GET['id']);
    $action = $_GET['action'];
    $notes = '';
    $admin_id = $_SESSION['user_id'];
    
    // Show appropriate modal via JavaScript later
    $show_modal = $action;
}
// Handle form POST actions: approve, reject registration
else if (isset($_POST['action']) && isset($_POST['registration_id'])) {
    $registration_id = intval($_POST['registration_id']);
    $action = $_POST['action'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $admin_id = $_SESSION['user_id'];
    
    // Get the registration data to access the room_id
    $stmt = $conn->prepare("SELECT room_id FROM hostel_registrations WHERE id = ?");
    $stmt->bind_param("i", $registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $registration = $result->fetch_assoc();
    $stmt->close();
    
    if ($registration) {
        $room_id = $registration['room_id'];
        
        if ($action === 'approve') {
            // Update registration status to Approved
            $status = 'Approved';
            $approved_check_in_date = isset($_POST['approved_check_in_date']) ? $_POST['approved_check_in_date'] : date('Y-m-d', strtotime('+3 days'));
            $approved_check_out_date = isset($_POST['approved_check_out_date']) ? $_POST['approved_check_out_date'] : date('Y-m-d', strtotime('+6 months'));
            
            $stmt = $conn->prepare("UPDATE hostel_registrations SET status = ?, notes = ?, admin_id = ?, processed_at = NOW(), approved_check_in_date = ?, approved_check_out_date = ? WHERE id = ?");
            $stmt->bind_param("ssissi", $status, $notes, $admin_id, $approved_check_in_date, $approved_check_out_date, $registration_id);
            $stmt->execute();
            $stmt->close();
            
            // Update room status to Occupied
            $stmt = $conn->prepare("UPDATE rooms SET availability_status = 'Occupied' WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            $message = "Registration #" . $registration_id . " has been approved.";
            $message_type = "success";
        } 
        else if ($action === 'reject') {
            // Update registration status to Rejected
            $status = 'Rejected';
            
            $stmt = $conn->prepare("UPDATE hostel_registrations SET status = ?, notes = ?, admin_id = ?, processed_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssii", $status, $notes, $admin_id, $registration_id);
            $stmt->execute();
            $stmt->close();
            
            // Update room status back to Available
            $stmt = $conn->prepare("UPDATE rooms SET availability_status = 'Available' WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            $message = "Registration #" . $registration_id . " has been rejected.";
            $message_type = "warning";
        }
    } else {
        $message = "Registration not found.";
        $message_type = "danger";
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled by Student', 'Checked In', 'Checked Out', 'all'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Build the query with optional status filter
$query = "
    SELECT hr.*, 
           s.name as student_name, 
           s.ic_number as student_id_number,
           r.room_number, 
           r.type as room_type, 
           r.price, 
           hb.block_name,
           a.name as admin_name
    FROM hostel_registrations hr
    JOIN students s ON hr.student_id = s.id
    JOIN rooms r ON hr.room_id = r.id
    JOIN hostel_blocks hb ON r.block_id = hb.id
    LEFT JOIN admins a ON hr.admin_id = a.id
";

// Apply status filter if not "all"
if ($status_filter !== 'all') {
    $query .= " WHERE hr.status = ?";
}

$query .= " ORDER BY hr.registration_date DESC";

if ($status_filter !== 'all') {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status_filter);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
$registrations = [];

while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}
$stmt->close();

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Room Registrations";
$additionalCSS = ["css/room_registrations.css", "css/dashboard.css"];
$additionalJS = ["https://code.jquery.com/jquery-3.6.0.min.js", "https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<div class="main-content">
    <?php
    $pageHeading = "Room Registration Requests";
    $breadcrumbs = [
        ['Home', 'dashboard.php'],
        ['Room Registrations', '#']
    ];
    require_once 'admin-content-header.php';
    ?>
    
    <div class="room-registrations-container">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
          <h4 class="filter-heading">Filter Registration Requests</h4>
        <div class="filter-controls">
            <div class="status-tabs">
                <a href="?status=all" class="status-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list-ul"></i> All Requests
                </a>
                <a href="?status=Pending" class="status-tab <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="?status=Approved" class="status-tab <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Approved
                </a>
                <a href="?status=Rejected" class="status-tab <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Rejected
                </a>
                <a href="?status=Cancelled by Student" class="status-tab <?php echo $status_filter === 'Cancelled by Student' ? 'active' : ''; ?>">
                    <i class="fas fa-ban"></i> Cancelled
                </a>
            </div>
        </div>          <?php if (empty($registrations)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>No Registration Requests Found</h3>
                <p>There are no room registration requests matching the "<?php echo ucfirst($status_filter); ?>" filter.</p>
                <?php if ($status_filter !== 'all'): ?>
                    <a href="?status=all" class="btn btn-primary mt-3">
                        <i class="fas fa-list-ul mr-2"></i> View All Requests
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>            <?php foreach ($registrations as $registration): ?>
                <div class="registration-card" 
                     data-id="<?php echo $registration['id']; ?>" 
                     data-status="<?php echo $registration['status']; ?>">
                    <div class="registration-header">
                        <span class="registration-id"><i class="fas fa-clipboard-check mr-2"></i> Registration #<?php echo $registration['id']; ?></span>
                        <?php 
                        $statusClass = '';
                        switch ($registration['status']) {
                            case 'Pending':
                                $statusClass = 'status-pending';
                                break;
                            case 'Approved':
                                $statusClass = 'status-approved';
                                break;
                            case 'Rejected':
                                $statusClass = 'status-rejected';
                                break;
                            case 'Cancelled by Student':
                                $statusClass = 'status-cancelled';
                                break;
                            case 'Checked In':
                                $statusClass = 'status-checkedin';
                                break;
                            case 'Checked Out':
                                $statusClass = 'status-checkedout';
                                break;
                        }
                        ?>
                        <span class="registration-status <?php echo $statusClass; ?>">
                            <?php echo $registration['status']; ?>
                        </span>
                    </div>
                    <div class="registration-details">
                        <div class="student-details">
                            <h4>Student Information</h4>
                            <div class="detail-row">
                                <span class="detail-label">Name:</span>
                                <span><?php echo htmlspecialchars($registration['student_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Student ID:</span>
                                <span><?php echo htmlspecialchars($registration['student_id_number']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Registration Date:</span>
                                <span><?php echo date('M d, Y', strtotime($registration['registration_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Requested Check-in:</span>
                                <span><?php echo $registration['requested_check_in_date'] ? date('M d, Y', strtotime($registration['requested_check_in_date'])) : 'Not specified'; ?></span>
                            </div>
                            <?php if ($registration['status'] === 'Approved'): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Approved Check-in:</span>
                                    <span><?php echo $registration['approved_check_in_date'] ? date('M d, Y', strtotime($registration['approved_check_in_date'])) : 'Not specified'; ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Approved Check-out:</span>
                                    <span><?php echo $registration['approved_check_out_date'] ? date('M d, Y', strtotime($registration['approved_check_out_date'])) : 'Not specified'; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="room-details">
                            <h4>Room Information</h4>
                            <div class="detail-row">
                                <span class="detail-label">Block:</span>
                                <span><?php echo htmlspecialchars($registration['block_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Room Number:</span>
                                <span><?php echo htmlspecialchars($registration['room_number']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Room Type:</span>
                                <span><?php echo htmlspecialchars($registration['room_type']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Price/Semester:</span>
                                <span>RM <?php echo number_format($registration['price'], 2); ?></span>
                            </div>
                            <?php if (!empty($registration['notes'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Admin Notes:</span>
                                    <span><?php echo htmlspecialchars($registration['notes']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($registration['admin_name'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Processed By:</span>
                                    <span><?php echo htmlspecialchars($registration['admin_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($registration['processed_at'])): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Processed On:</span>
                                    <span><?php echo date('M d, Y H:i', strtotime($registration['processed_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                      <?php if ($registration['status'] === 'Pending'): ?>
                        <div class="registration-actions">
                            <button class="btn btn-approve" onclick="showApproveModal(<?php echo $registration['id']; ?>)">
                                <i class="fas fa-check-circle"></i> Approve Registration
                            </button>
                            <button class="btn btn-reject" onclick="showRejectModal(<?php echo $registration['id']; ?>)">
                                <i class="fas fa-times-circle"></i> Reject Registration
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel"><i class="fas fa-check-circle text-success mr-2"></i> Approve Registration</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="registration_id" id="approve_registration_id">
                    
                    <div class="form-group">
                        <label for="approved_check_in_date">Approved Check-in Date</label>
                        <input type="date" class="form-control" id="approved_check_in_date" name="approved_check_in_date" 
                               value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="approved_check_out_date">Approved Check-out Date</label>
                        <input type="date" class="form-control" id="approved_check_out_date" name="approved_check_out_date" 
                               value="<?php echo date('Y-m-d', strtotime('+6 months')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="approve_notes">Notes (Optional)</label>
                        <textarea class="form-control notes-field" id="approve_notes" name="notes" 
                                  placeholder="Add any notes or special instructions for the student"></textarea>
                    </div>
                </div>                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-approve"><i class="fas fa-check-circle"></i> Approve Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel"><i class="fas fa-times-circle text-danger mr-2"></i> Reject Registration</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="registration_id" id="reject_registration_id">
                    
                    <div class="form-group">
                        <label for="reject_notes">Reason for Rejection</label>
                        <textarea class="form-control notes-field" id="reject_notes" name="notes" 
                                  placeholder="Please explain the reason for rejecting this registration request" required></textarea>
                    </div>
                </div>                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-reject"><i class="fas fa-times-circle"></i> Reject Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>    function showApproveModal(registrationId) {
        document.getElementById('approve_registration_id').value = registrationId;
        $('#approveModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
        
        // Center the modal
        setTimeout(function() {
            $('.modal-dialog').css({
                'margin-top': Math.max(0, ($(window).height() - $('.modal-dialog').height()) / 2)
            });
        }, 200);
    }
    
    function showRejectModal(registrationId) {
        document.getElementById('reject_registration_id').value = registrationId;
        $('#rejectModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });
        
        // Center the modal
        setTimeout(function() {
            $('.modal-dialog').css({
                'margin-top': Math.max(0, ($(window).height() - $('.modal-dialog').height()) / 2)
            });
        }, 200);
    }
    
    // Check if we need to show a modal on page load (from URL parameters)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($show_modal) && isset($registration_id)): ?>
            <?php if ($show_modal === 'approve'): ?>
                showApproveModal(<?php echo $registration_id; ?>);
            <?php elseif ($show_modal === 'reject'): ?>
                showRejectModal(<?php echo $registration_id; ?>);
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (isset($_GET['id']) && !isset($_GET['action'])): ?>
            // Highlight the registration with the given ID
            const registrationId = <?php echo intval($_GET['id']); ?>;
            const registration = document.querySelector(`.registration-card[data-id="${registrationId}"]`);
            if (registration) {
                registration.scrollIntoView({ behavior: 'smooth', block: 'center' });
                registration.classList.add('highlighted');
                
                // Remove the highlight after a few seconds
                setTimeout(() => {
                    registration.classList.remove('highlighted');
                }, 3000);
            }
        <?php endif; ?>
    });
</script>

<?php require_once '../shared/includes/footer.php'; ?>
