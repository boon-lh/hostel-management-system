<?php
/**
 * Payment Receipt Page
 * Hostel Management System - Admin Panel
 * 
 * This page displays payment receipts/history for a specific student
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

// Fetch payment history with bill details
$payments_query = "SELECT p.*, b.semester, b.academic_year, b.amount as bill_amount,
                   i.invoice_number
                   FROM payments p
                   JOIN bills b ON p.bill_id = b.id
                   LEFT JOIN invoices i ON p.id = i.payment_id
                   WHERE p.student_id = ?
                   ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$payments_result = $stmt->get_result();
$payments = [];
while ($row = $payments_result->fetch_assoc()) {
    $payments[] = $row;
}

// Set page title and additional CSS files
$pageTitle = "Payment Receipts - " . $student['name'] . " - MMU Hostel Management";
$additionalCSS = ["css/header.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>Payment Receipts - <?php echo htmlspecialchars($student['name']); ?></h1>
        <div class="user-info">
            <?php 
            if (isset($_SESSION["profile_image"]) && !empty($_SESSION["profile_image"])) {
                if (strpos($_SESSION["profile_image"], '../uploads/profile_pictures/') === 0) {
                    echo '<img src="' . $_SESSION["profile_image"] . '" alt="Admin Profile">';
                } else {
                    echo '<img src="../uploads/profile_pictures/' . $_SESSION["profile_image"] . '" alt="Admin Profile">';
                }
            } else {
                echo '<img src="../uploads/profile_pictures/default_admin.png" alt="Admin Profile">';
            }
            ?>
            <span class="user-name"><?php echo $_SESSION["fullname"] ?? $_SESSION["user"]; ?></span>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title-area">
                <div class="card-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h2 class="card-title">Payment History & Receipts</h2>
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
            
            <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Invoice Number</th>
                                <th>Payment Date</th>
                                <th>Amount (RM)</th>
                                <th>Payment Method</th>
                                <th>Reference Number</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['invoice_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('F j, Y g:i A', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['academic_year']); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($payment['status']) {
                                            case 'completed':
                                                $statusClass = 'status-paid';
                                                break;
                                            case 'pending':
                                                $statusClass = 'status-pending';
                                                break;
                                            case 'failed':
                                                $statusClass = 'status-inactive';
                                                break;
                                            case 'refunded':
                                                $statusClass = 'status-overdue';
                                                break;
                                        }
                                        ?>
                                        <span class="status <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($payment['status'] === 'completed' && !empty($payment['invoice_number'])): ?>
                                            <button class="btn-action" onclick="viewReceipt(<?php echo $payment['id']; ?>)" title="View Receipt">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action" onclick="printReceipt(<?php echo $payment['id']; ?>)" title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">No receipt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="payment-summary">
                    <div class="summary-card">
                        <h4>Payment Summary</h4>
                        <div class="summary-stats">
                            <?php
                            $totalPaid = array_sum(array_column($payments, 'amount'));
                            $completedPayments = array_filter($payments, function($p) { return $p['status'] === 'completed'; });
                            $pendingPayments = array_filter($payments, function($p) { return $p['status'] === 'pending'; });
                            ?>
                            <div class="stat">
                                <span class="label">Total Payments:</span>
                                <span class="value"><?php echo count($payments); ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Completed Payments:</span>
                                <span class="value"><?php echo count($completedPayments); ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Pending Payments:</span>
                                <span class="value"><?php echo count($pendingPayments); ?></span>
                            </div>
                            <div class="stat total">
                                <span class="label">Total Amount Paid:</span>
                                <span class="value">RM <?php echo number_format($totalPaid, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="no-data-message">
                    <p>No payment history found for this student.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receipt-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="receipt-content">
            <!-- Receipt content will be loaded here -->
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

.payment-summary {
    margin-top: 30px;
}

.summary-card {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 20px;
    max-width: 400px;
}

.summary-card h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.summary-stats .stat {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e3e6f0;
}

.summary-stats .stat:last-child {
    border-bottom: none;
}

.summary-stats .stat.total {
    font-weight: 600;
    color: #1cc88a;
    border-top: 2px solid #1cc88a;
    margin-top: 10px;
    padding-top: 15px;
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

.btn-action {
    background: none;
    border: none;
    color: #4e73df;
    cursor: pointer;
    padding: 5px;
    margin: 0 2px;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.btn-action:hover {
    background: #4e73df;
    color: white;
}

.text-muted {
    color: #858796;
    font-size: 12px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    position: relative;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 20px;
    top: 15px;
}

.close:hover {
    color: #000;
}
</style>

<script>
// Modal functionality
const modal = document.getElementById('receipt-modal');
const closeBtn = document.querySelector('.close');

closeBtn.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// View receipt function
function viewReceipt(paymentId) {
    const content = document.getElementById('receipt-content');
    content.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading receipt...</div>';
    modal.style.display = "block";
    
    // Here you would typically make an AJAX call to fetch receipt details
    // For now, we'll show a placeholder
    setTimeout(() => {
        content.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <h3>Payment Receipt</h3>
                <p>Payment ID: ${paymentId}</p>
                <p style="color: #858796;">Receipt details would be displayed here.</p>
                <p style="color: #858796;">This feature can be enhanced to show detailed receipt information.</p>
            </div>
        `;
    }, 500);
}

// Print receipt function
function printReceipt(paymentId) {
    // Here you would typically generate a printable receipt
    alert(`Print receipt for Payment ID: ${paymentId}\n\nThis feature can be enhanced to generate printable receipts.`);
}
</script>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>

