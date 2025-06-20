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
$student_name = $_SESSION['student_name'] ?? 'Student';
// Check for any messages in the session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Clear session messages after retrieving them
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Handle cancellation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registration'])) {
    $registration_id = $_POST['registration_id'];
    
    // Check if this registration belongs to the current student
    $verify_stmt = $conn->prepare("SELECT id FROM hostel_registrations WHERE id = ? AND student_id = ?");
    $verify_stmt->bind_param("ii", $registration_id, $student_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Only allow cancellation if the status is 'Pending'
        $update_stmt = $conn->prepare("UPDATE hostel_registrations SET status = 'Cancelled by Student' WHERE id = ? AND student_id = ? AND status = 'Pending'");
        $update_stmt->bind_param("ii", $registration_id, $student_id);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows > 0) {
            $message = "Registration successfully cancelled.";
            $message_type = "success";
            
            // Also update room availability back to Available
            $room_stmt = $conn->prepare("
                UPDATE rooms r
                JOIN hostel_registrations hr ON r.id = hr.room_id
                SET r.availability_status = 'Available'
                WHERE hr.id = ?
            ");
            $room_stmt->bind_param("i", $registration_id);
            $room_stmt->execute();
        } else {
            $message = "Unable to cancel registration. It may already be processed or approved.";
            $message_type = "warning";
        }
        $update_stmt->close();
    } else {
        $message = "Invalid registration request.";
        $message_type = "danger";
    }
    $verify_stmt->close();
}

// Fetch all registrations for the current student
$stmt = $conn->prepare("
    SELECT hr.*, r.room_number, r.type as room_type, r.price as rate_per_semester, hb.block_name  
    FROM hostel_registrations hr
    JOIN rooms r ON hr.room_id = r.id
    JOIN hostel_blocks hb ON r.block_id = hb.id
    WHERE hr.student_id = ?
    ORDER BY hr.registration_date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations = [];

while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}
$stmt->close();
?>

<link rel="stylesheet" href="css/my_registrations.css">

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-history"></i> My Room Registrations</h2>
            <p>View and manage your hostel room registrations.</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">                <?php if (empty($registrations)): ?>
                    <div class="no-registrations">
                        <i class="fas fa-bed"></i>
                        <p>You don't have any room registrations yet.</p>
                        <p class="text-muted">Each student can only register for one room at a time.</p>
                        <a href="hostel_registration.php" class="btn btn-primary">Register for a Room</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table registration-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Room</th>
                                    <th>Room Type</th>
                                    <th>Rate/Semester</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $reg): ?>
                                    <tr>
                                        <td><?php echo $reg['id']; ?></td>
                                        <td>
                                            <span class="room-info">
                                                <?php echo htmlspecialchars($reg['room_number']); ?>
                                                <small>(<?php echo htmlspecialchars($reg['block_name']); ?>)</small>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($reg['room_type']); ?></td>
                                        <td><?php echo number_format($reg['rate_per_semester']); ?> MYR</td>
                                        <td><?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></td>
                                        <td>
                                            <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $reg['status'])); ?>">
                                                <?php echo htmlspecialchars($reg['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge payment-<?php echo strtolower($reg['payment_status']); ?>">
                                                <?php echo htmlspecialchars($reg['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($reg['status'] === 'Pending'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this registration?');">
                                                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                    <button type="submit" name="cancel_registration" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </form>                                            <?php elseif ($reg['status'] === 'Approved' && $reg['payment_status'] === 'Unpaid'): ?>
                                                <a href="../payment/payment.php?reg_id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-money-bill"></i> Pay Now
                                                </a>
                                            <?php elseif ($reg['status'] === 'Checked In'): ?>
                                                <a href="room_details.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-info-circle"></i> Details
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fas fa-ban"></i> No Action
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                      <div class="registration-summary">
                        <h4>Status Legend</h4>
                        <div class="status-legend">
                            <div class="legend-item">
                                <span class="badge status-pending">Pending</span>
                                <p>Registration is awaiting admin approval</p>
                            </div>
                            <div class="legend-item">
                                <span class="badge status-approved">Approved</span>
                                <p>Registration has been approved, payment required</p>
                            </div>
                            <div class="legend-item">
                                <span class="badge status-checked-in">Checked In</span>
                                <p>You've successfully checked in to the room</p>
                            </div>
                            <div class="legend-item">
                                <span class="badge status-rejected">Rejected</span>
                                <p>Registration was rejected by admin</p>
                            </div>
                            <div class="legend-item">
                                <span class="badge status-cancelled-by-student">Cancelled by Student</span>
                                <p>You cancelled this registration</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../shared/includes/footer.php';
?>
