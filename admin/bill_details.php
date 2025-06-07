<?php
/**
 * Bill Details Page
 * Hostel Management System - Admin Panel
 * 
 * This page displays detailed billing information for a specific student
 */

session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

// Get student ID from URL parameter
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($studentId === 0) {
    header("Location: students.php");
    exit();
}

// Fetch student information
$student_query = "SELECT id, name, username FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    header("Location: students.php");
    exit();
}

$student = $student_result->fetch_assoc();

// Fetch detailed bill information
$bills_query = "SELECT b.*, 
                COALESCE(SUM(p.amount), 0) as amount_paid,
                (b.amount - COALESCE(SUM(p.amount), 0)) as balance
                FROM bills b
                LEFT JOIN payments p ON b.id = p.bill_id AND p.status = 'completed'
                WHERE b.student_id = ?
                GROUP BY b.id
                ORDER BY b.due_date DESC";
$stmt = $conn->prepare($bills_query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$bills_result = $stmt->get_result();
$bills = [];
while ($row = $bills_result->fetch_assoc()) {
    $bills[] = $row;
}

// Set page title and additional CSS files
$pageTitle = "Bill Details - " . $student['name'] . " - MMU Hostel Management";
$contentTitle = "Bill Details - " . $student['name'];
$additionalCSS = ["css/header.css"];

// Include admin header
require_once '../shared/includes/admin-header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';

// Include admin content header
require_once '../shared/includes/admin-content-header.php';
?>

<!-- Bill details content starts here -->

    <div class="card">
        <div class="card-header">
            <div class="card-title-area">
                <div class="card-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h2 class="card-title">Billing History</h2>
            </div>
            <div class="card-actions">
                <a href="students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>
        </div>
        
        <div class="card-content">
            <div class="student-info-summary">
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['id']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
            </div>
            
            <?php if (count($bills) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Amount (RM)</th>
                                <th>Amount Paid (RM)</th>
                                <th>Balance (RM)</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bills as $bill): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['id']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['academic_year']); ?></td>
                                    <td><?php echo number_format($bill['amount'], 2); ?></td>
                                    <td><?php echo number_format($bill['amount_paid'], 2); ?></td>
                                    <td><?php echo number_format($bill['balance'], 2); ?></td>
                                    <td><?php echo date('F j, Y', strtotime($bill['due_date'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($bill['status']) {
                                            case 'paid':
                                                $statusClass = 'status-paid';
                                                break;
                                            case 'partially_paid':
                                                $statusClass = 'status-pending';
                                                break;
                                            case 'unpaid':
                                                $statusClass = 'status-inactive';
                                                break;
                                            case 'overdue':
                                                $statusClass = 'status-overdue';
                                                break;
                                        }
                                        ?>
                                        <span class="status <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $bill['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('F j, Y', strtotime($bill['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data-message">
                    <p>No billing information found for this student.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.student-info-summary {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.student-info-summary p {
    margin: 5px 0;
    color: #5a5c69;
}

.no-data-message {
    text-align: center;
    padding: 40px;
    color: #858796;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-secondary {
    background: #858796;
    color: white;
}

.btn-secondary:hover {
    background: #5a5c69;
    color: white;
}

.btn i {
    margin-right: 8px;
}
</style>

<?php
// Include admin footer
require_once '../shared/includes/admin-footer.php';
?>

