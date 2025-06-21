<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Admin Dashboard";
$additionalCSS = ["css/dashboard.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';

// Include database connection
require_once '../shared/includes/db_connection.php';

// Calculate statistics from database
// 1. Total Residents (students with active hostel registrations)
$residentsQuery = "SELECT COUNT(DISTINCT student_id) as total_residents 
                  FROM hostel_registrations 
                  WHERE status IN ('Approved', 'Checked In')";
$residentsResult = $conn->query($residentsQuery);
$residentsRow = $residentsResult->fetch_assoc();
$totalResidents = $residentsRow['total_residents'];

// 2. Occupancy Rate - Updated to reflect only confirmed occupied rooms
$roomsQuery = "SELECT 
                COUNT(*) as total_rooms,
                SUM(CASE WHEN availability_status = 'Occupied' THEN 1 ELSE 0 END) as occupied_rooms
               FROM rooms 
               WHERE availability_status NOT IN ('Under Maintenance')";
$roomsResult = $conn->query($roomsQuery);
$roomsRow = $roomsResult->fetch_assoc();
$totalRooms = $roomsRow['total_rooms'];
$occupiedRooms = $roomsRow['occupied_rooms'];
$occupancyRate = ($totalRooms > 0) ? round(($occupiedRooms / $totalRooms) * 100) : 0;

// Queries for Pending Maintenance and Monthly Revenue have been removed

// Recent Applications (hostel registrations)
$applicationsQuery = "SELECT hr.id, hr.student_id, s.name, s.ic_number, hr.registration_date, hr.status 
                     FROM hostel_registrations hr
                     JOIN students s ON hr.student_id = s.id
                     ORDER BY hr.registration_date DESC
                     LIMIT 3";
$applicationsResult = $conn->query($applicationsQuery);

// Room Status
$roomStatusQuery = "SELECT r.room_number, hb.block_name, r.type, r.availability_status, r.id
                   FROM rooms r
                   JOIN hostel_blocks hb ON r.block_id = hb.id
                   ORDER BY r.updated_at DESC
                   LIMIT 4";
$roomStatusResult = $conn->query($roomStatusQuery);

// Recent Payments
$paymentsQuery = "SELECT p.id, p.student_id, s.ic_number, p.amount, p.payment_date, p.status
                 FROM payments p
                 JOIN students s ON p.student_id = s.id
                 ORDER BY p.payment_date DESC
                 LIMIT 4";
$paymentsResult = $conn->query($paymentsQuery);

// Count pending room registration requests
$pendingRegistrationsQuery = "SELECT COUNT(*) as count FROM hostel_registrations WHERE status = 'Pending'";
$pendingRegistrationsResult = $conn->query($pendingRegistrationsQuery);
$pendingRegistrationsCount = 0;
if ($pendingRegistrationsResult && $pendingRegistrationsResult->num_rows > 0) {
    $pendingRegistrationsCount = $pendingRegistrationsResult->fetch_assoc()['count'];
}

// Count open complaints
$openComplaintsQuery = "SELECT COUNT(*) as count FROM complaints WHERE status IN ('Open', 'In Progress')";
$openComplaintsResult = $conn->query($openComplaintsQuery);
$openComplaintsCount = 0;
if ($openComplaintsResult && $openComplaintsResult->num_rows > 0) {
    $openComplaintsCount = $openComplaintsResult->fetch_assoc()['count'];
}

// Complaints and Feedback
$complaintsQuery = "SELECT c.id, c.subject, c.description, c.status, c.created_at, c.priority,
                    s.name as student_name, s.id as student_id  
                   FROM complaints c
                   JOIN students s ON c.student_id = s.id
                   ORDER BY c.created_at DESC
                   LIMIT 3";
$complaintsResult = $conn->query($complaintsQuery);

// Remove reference to old $requestsResult variable to avoid errors
$requestsResult = null;
?>

<!-- Main Content -->
<div class="main-content">
    <?php 
    $pageHeading = "Admin Dashboard";
    require_once 'admin-content-header.php'; 
    ?>    <!-- Stats Overview -->
    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $totalResidents; ?></h3>
                <p>Total Residents</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-door-open"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $occupancyRate; ?>%</h3>
                <p>Occupancy Rate</p>
            </div>
        </div>
          <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $pendingRegistrationsCount; ?></h3>
                <p>Pending Registrations</p>
            </div>
            <?php if ($pendingRegistrationsCount > 0): ?>
            <a href="room_registrations.php?status=Pending" class="stat-action">
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comment-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $openComplaintsCount; ?></h3>
                <p>Open Complaints</p>
            </div>
            <?php if ($openComplaintsCount > 0): ?>
            <a href="complaints.php?status=Open" class="stat-action">
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
  
  
    </div>

    <!-- Dashboard Cards -->
    <div class="dashboard-cards">
        <!-- Recent Applications -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-area">
                    <div class="card-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h2 class="card-title">Recent Applications</h2>
                </div>                <div class="card-actions">
                    <a href="room_registrations.php">View All</a>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="data-table">                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($applicationsResult->num_rows > 0): ?>
                                <?php while ($app = $applicationsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['ic_number']); ?></td>
                                        <td><?php echo htmlspecialchars($app['name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($app['registration_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'status-pending';
                                            if ($app['status'] == 'Approved') $statusClass = 'status-approved';
                                            else if ($app['status'] == 'Rejected') $statusClass = 'status-rejected';
                                            else if ($app['status'] == 'Cancelled by Student') $statusClass = 'status-cancelled';
                                            ?>
                                            <span class="status <?php echo $statusClass; ?>"><?php echo $app['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No recent applications</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Room Status -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-area">
                    <div class="card-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h2 class="card-title">Room Status</h2>
                </div>
                <div class="card-actions">
                    <a href="block_rooms.php">View All</a>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room No.</th>
                                <th>Block</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($roomStatusResult->num_rows > 0): ?>
                                <?php while ($room = $roomStatusResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($room['block_name']); ?></td>
                                        <td><?php echo htmlspecialchars($room['type']); ?></td>
                                        <td>
                                            <?php 
                                            $roomStatusClass = '';
                                            switch ($room['availability_status']) {
                                                case 'Available':
                                                    $roomStatusClass = 'status-vacant';
                                                    break;
                                                case 'Occupied':
                                                    $roomStatusClass = 'status-occupied';
                                                    break;
                                                case 'Under Maintenance':
                                                    $roomStatusClass = 'status-maintenance';
                                                    break;
                                                case 'Pending Confirmation':
                                                    $roomStatusClass = 'status-pending';
                                                    break;
                                                default:
                                                    $roomStatusClass = 'status-reserved';
                                            }                                            ?>
                                            <span class="status <?php echo $roomStatusClass; ?>"><?php echo $room['availability_status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No rooms found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-area">
                    <div class="card-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h2 class="card-title">Recent Payments</h2>
                </div>
                <div class="card-actions">
                    <a href="finance.php">View All</a>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($paymentsResult->num_rows > 0): ?>
                                <?php while ($payment = $paymentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['ic_number']); ?></td>
                                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $paymentStatusClass = 'status-paid';
                                            if ($payment['status'] == 'pending') $paymentStatusClass = 'status-pending';
                                            else if ($payment['status'] == 'failed') $paymentStatusClass = 'status-overdue';
                                            else if ($payment['status'] == 'refunded') $paymentStatusClass = 'status-refunded';
                                            ?>
                                            <span class="status <?php echo $paymentStatusClass; ?>"><?php echo ucfirst($payment['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No recent payments</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>        <!-- Complaints and Feedback -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-area">
                    <div class="card-icon">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <h2 class="card-title">Complaints and Feedback</h2>
                </div>
                <div class="card-actions">
                    <a href="complaints.php">View All</a>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>                            <tr>                                <th>Student</th>
                                <th>Subject</th>
                                <th>Priority</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($complaintsResult && $complaintsResult->num_rows > 0): ?>
                                <?php while ($complaint = $complaintsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($complaint['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($complaint['priority'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'status-pending';
                                            if ($complaint['status'] == 'in_progress') $statusClass = 'status-in-progress';
                                            else if ($complaint['status'] == 'resolved') $statusClass = 'status-approved';
                                            else if ($complaint['status'] == 'closed') $statusClass = 'status-completed';
                                            ?>
                                            <span class="status <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>                                <tr>
                                    <td colspan="5" class="text-center">No complaints or feedback found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>