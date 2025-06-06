<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Billing";
$additionalCSS = ["css/billing.css", "css/billing-additional.css", "css/pdf-styles.css"];
$additionalJS = ["https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js", "js/pdf-generator.js"];

// Get student ID from session
$username = $_SESSION["user"];
$studentId = 0;
$errors = [];
$success = "";
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'bills';

// Get student ID from database
$stmt = $conn->prepare("SELECT id FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentId = $row['id'];
} else {
    $errors[] = "Student information not found.";
}

// Initialize arrays to hold data
$bills = [];
$payments = [];
$invoices = [];
$refunds = [];

// Check if we need to process a payment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'make_payment') {
        $billId = $_POST['bill_id'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $paymentMethod = $_POST['payment_method'] ?? '';
        $referenceNumber = $_POST['reference_number'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Basic validation
        if (empty($billId)) $errors[] = "Bill ID is required";
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) $errors[] = "Valid amount is required";
        if (empty($paymentMethod)) $errors[] = "Payment method is required";
        
        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert payment
                $stmt = $conn->prepare("INSERT INTO payments (bill_id, student_id, amount, payment_method, reference_number, notes) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidsss", $billId, $studentId, $amount, $paymentMethod, $referenceNumber, $notes);
                $stmt->execute();
                $paymentId = $conn->insert_id;
                
                // Generate invoice number (format: INV-YYYYMMDD-XXXX where XXXX is the payment ID)
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($paymentId, 4, '0', STR_PAD_LEFT);
                
                // Insert invoice
                $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, payment_id, student_id) 
                                        VALUES (?, ?, ?)");
                $stmt->bind_param("sii", $invoiceNumber, $paymentId, $studentId);
                $stmt->execute();
                
                // Update bill status
                // First get the bill amount and current amount paid
                $stmt = $conn->prepare("SELECT b.amount, 
                                        COALESCE(SUM(p.amount), 0) as total_paid
                                        FROM bills b
                                        LEFT JOIN payments p ON b.id = p.bill_id AND p.status = 'completed'
                                        WHERE b.id = ?
                                        GROUP BY b.id");
                $stmt->bind_param("i", $billId);
                $stmt->execute();
                $billResult = $stmt->get_result();
                $billData = $billResult->fetch_assoc();
                
                $totalAmount = $billData['amount'];
                $totalPaid = $billData['total_paid'] + $amount; // Add the new payment
                
                // Determine the new status
                $status = 'unpaid';
                if ($totalPaid >= $totalAmount) {
                    $status = 'paid';
                } else if ($totalPaid > 0) {
                    $status = 'partially_paid';
                }
                
                // Update the bill status
                $stmt = $conn->prepare("UPDATE bills SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $billId);
                $stmt->execute();
                
                // Commit the transaction
                $conn->commit();
                
                $success = "Payment processed successfully! Your invoice has been generated.";
                $activeTab = 'invoices'; // Switch to invoices tab
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $errors[] = "Error processing payment: " . $e->getMessage();
            }
        }
    } else if ($_POST['action'] === 'request_refund') {
        $paymentId = $_POST['payment_id'] ?? 0;
        $amount = $_POST['refund_amount'] ?? 0;
        $reason = $_POST['refund_reason'] ?? '';
        
        // Basic validation
        if (empty($paymentId)) $errors[] = "Payment ID is required";
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) $errors[] = "Valid refund amount is required";
        if (empty($reason)) $errors[] = "Refund reason is required";
        
        if (empty($errors)) {
            // Check if payment exists and belongs to student
            $stmt = $conn->prepare("SELECT amount FROM payments WHERE id = ? AND student_id = ? AND status = 'completed'");
            $stmt->bind_param("ii", $paymentId, $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $errors[] = "Invalid payment selected for refund";
            } else {
                $paymentData = $result->fetch_assoc();
                if ($amount > $paymentData['amount']) {
                    $errors[] = "Refund amount cannot be greater than the payment amount";
                } else {
                    // Insert refund request
                    $stmt = $conn->prepare("INSERT INTO refunds (payment_id, student_id, amount, reason) 
                                            VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iids", $paymentId, $studentId, $amount, $reason);
                    
                    if ($stmt->execute()) {
                        $success = "Refund request submitted successfully! Your request is pending approval.";
                        $activeTab = 'refunds';
                    } else {
                        $errors[] = "Error submitting refund request: " . $conn->error;
                    }
                }
            }
        }
    }
}

// Fetch bills
if ($studentId > 0) {
    $stmt = $conn->prepare("SELECT b.*, 
                           r.room_type, r.block, r.price_per_semester,
                           COALESCE(SUM(p.amount), 0) as amount_paid
                           FROM bills b
                           LEFT JOIN room_rates r ON b.room_id = r.id
                           LEFT JOIN payments p ON b.id = p.bill_id AND p.status = 'completed'
                           WHERE b.student_id = ?
                           GROUP BY b.id
                           ORDER BY b.due_date DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }
    }
    
    // Fetch payments
    $stmt = $conn->prepare("SELECT p.*, b.semester, b.academic_year, b.amount as bill_amount 
                           FROM payments p
                           JOIN bills b ON p.bill_id = b.id
                           WHERE p.student_id = ?
                           ORDER BY p.payment_date DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
    }
    
    // Fetch invoices with payment details
    $stmt = $conn->prepare("SELECT i.*, p.amount, p.payment_method, p.payment_date, p.reference_number,
                           b.semester, b.academic_year
                           FROM invoices i
                           JOIN payments p ON i.payment_id = p.id
                           JOIN bills b ON p.bill_id = b.id
                           WHERE i.student_id = ?
                           ORDER BY i.generated_date DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $invoices[] = $row;
        }
    }
    
    // Fetch refunds
    $stmt = $conn->prepare("SELECT r.*, p.amount as payment_amount, p.payment_date, p.reference_number
                           FROM refunds r
                           JOIN payments p ON r.payment_id = p.id
                           WHERE r.student_id = ?
                           ORDER BY r.requested_date DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $refunds[] = $row;
        }
    }
}

// Include header
require_once '../shared/includes/header.php';

// Include student sidebar
require_once '../shared/includes/sidebar-student.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1><i class="fas fa-file-invoice-dollar"></i> Hostel Billing</h1>
        
    </div>
    
    <div class="billing-container">
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
          <!-- Tabs Navigation -->
        <div class="shadow-sm">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab == 'bills' ? 'active' : ''; ?>" href="?tab=bills">
                        <i class="fas fa-file-invoice-dollar"></i> Bills
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab == 'payments' ? 'active' : ''; ?>" href="?tab=payments">
                        <i class="fas fa-money-bill-wave"></i> Payment History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab == 'invoices' ? 'active' : ''; ?>" href="?tab=invoices">
                        <i class="fas fa-file-invoice"></i> Invoices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeTab == 'refunds' ? 'active' : ''; ?>" href="?tab=refunds">
                        <i class="fas fa-undo-alt"></i> Refunds
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Bills Tab -->
            <div class="tab-pane <?php echo $activeTab == 'bills' ? 'active' : ''; ?>" id="bills">
                <div class="section-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Your Bills</h2>
                </div>
                
                <?php if (!empty($bills)): ?>
                    <?php foreach ($bills as $bill): ?>
                        <?php
                        $badgeClass = 'badge-warning';
                        $badgeText = 'Unpaid';
                        
                        if ($bill['status'] === 'paid') {
                            $badgeClass = 'badge-success';
                            $badgeText = 'Paid';
                        } else if ($bill['status'] === 'partially_paid') {
                            $badgeClass = 'badge-info';
                            $badgeText = 'Partially Paid';
                        } else if ($bill['status'] === 'overdue') {
                            $badgeClass = 'badge-danger';
                            $badgeText = 'Overdue';
                        }
                        
                        $remainingAmount = $bill['amount'] - $bill['amount_paid'];
                        ?>
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col">
                                        <h3>
                                            <?php echo $bill['semester'] . ' ' . $bill['academic_year']; ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php 
                                $billStatusIcon = '';
                                if ($bill['status'] === 'paid') {
                                    $billStatusIcon = '<i class="fas fa-check-circle"></i> ';
                                } else if ($bill['status'] === 'partially_paid') {
                                    $billStatusIcon = '<i class="fas fa-half-alt"></i> ';
                                } else if ($bill['status'] === 'overdue') {
                                    $billStatusIcon = '<i class="fas fa-exclamation-circle"></i> ';
                                } else {
                                    $billStatusIcon = '<i class="fas fa-clock"></i> ';
                                }
                                echo $billStatusIcon . $badgeText; 
                                ?>
                            </span>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="bill-details">
                                    <div class="row">
                                        <div class="col">
                                            <p><strong>Room Type:</strong> <?php echo $bill['room_type'] ?? 'N/A'; ?></p>
                                            <p><strong>Block:</strong> <?php echo $bill['block'] ?? 'N/A'; ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($bill['due_date'])); ?></p>
                                        </div>
                                        <div class="col">
                                            <p><strong>Total Amount:</strong> RM <?php echo number_format($bill['amount'], 2); ?></p>
                                            <p><strong>Amount Paid:</strong> RM <?php echo number_format($bill['amount_paid'], 2); ?></p>
                                            <p><strong>Remaining:</strong> RM <?php echo number_format($remainingAmount, 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($bill['status'] !== 'paid'): ?>
                                        <div class="payment-actions">
                                            <button class="btn btn-primary" onclick="showPaymentModal(<?php echo $bill['id']; ?>, <?php echo $remainingAmount; ?>)">
                                                <i class="fas fa-credit-card"></i> Make Payment
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>                    <div class="no-bills-message">
                        <p>You currently have no bills.</p>
                        <p class="text-muted">Your billing information will appear here once your hostel registration is processed.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payments Tab -->
            <div class="tab-pane <?php echo $activeTab == 'payments' ? 'active' : ''; ?>" id="payments">
                <div class="section-header">
                    <h2><i class="fas fa-money-bill-wave"></i> Payment History</h2>
                </div>
                
                <?php if (!empty($payments)): ?>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Semester</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                            <td><?php echo $payment['reference_number'] ?: 'N/A'; ?></td>
                                            <td><?php echo $payment['semester'] . ' ' . $payment['academic_year']; ?></td>                                            <td>
                                                <?php
                                                $statusClass = 'badge-info';
                                                $statusIcon = '<i class="fas fa-clock"></i> ';
                                                
                                                if ($payment['status'] === 'completed') {
                                                    $statusClass = 'badge-success';
                                                    $statusIcon = '<i class="fas fa-check-circle"></i> ';
                                                } else if ($payment['status'] === 'failed') {
                                                    $statusClass = 'badge-danger';
                                                    $statusIcon = '<i class="fas fa-times-circle"></i> ';
                                                } else if ($payment['status'] === 'refunded') {
                                                    $statusClass = 'badge-warning';
                                                    $statusIcon = '<i class="fas fa-undo-alt"></i> ';
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo $statusIcon . ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] === 'completed'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="showRefundModal(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                                                        <i class="fas fa-undo-alt"></i> Request Refund
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>                    <div class="no-payments-message">
                        <p>You haven't made any payments yet.</p>
                        <p class="text-muted">Your payment history will be displayed here once you make a payment.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Invoices Tab -->
            <div class="tab-pane <?php echo $activeTab == 'invoices' ? 'active' : ''; ?>" id="invoices">
                <div class="section-header">
                    <h2><i class="fas fa-file-invoice"></i> Invoices</h2>
                </div>
                
                <?php if (!empty($invoices)): ?>
                    <div class="row">
                        <?php foreach ($invoices as $invoice): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3>Invoice #<?php echo $invoice['invoice_number']; ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($invoice['generated_date'])); ?></p>
                                        <p><strong>Amount:</strong> RM <?php echo number_format($invoice['amount'], 2); ?></p>
                                        <p><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $invoice['payment_method'])); ?></p>
                                        <p><strong>Reference Number:</strong> <?php echo $invoice['reference_number'] ?: 'N/A'; ?></p>
                                        <p><strong>Semester:</strong> <?php echo $invoice['semester'] . ' ' . $invoice['academic_year']; ?></p>
                                        
                                        <div class="invoice-actions">
                                            <button class="btn btn-primary" onclick="viewInvoice(<?php echo htmlspecialchars(json_encode($invoice), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fas fa-eye"></i> View Invoice
                                            </button>
                                            <button class="btn btn-secondary" onclick="printInvoice(<?php echo htmlspecialchars(json_encode($invoice), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>                    <div class="no-invoices-message">
                        <p>You don't have any invoices yet.</p>
                        <p class="text-muted">Invoices are automatically generated after successful payments.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Refunds Tab -->
            <div class="tab-pane <?php echo $activeTab == 'refunds' ? 'active' : ''; ?>" id="refunds">
                <div class="section-header">
                    <h2><i class="fas fa-undo-alt"></i> Refunds</h2>
                </div>
                
                <?php if (!empty($refunds)): ?>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Request Date</th>
                                        <th>Payment Reference</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Processed Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($refunds as $refund): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($refund['requested_date'])); ?></td>
                                            <td><?php echo $refund['reference_number'] ?: 'N/A'; ?></td>
                                            <td>RM <?php echo number_format($refund['amount'], 2); ?></td>
                                            <td><?php echo $refund['reason']; ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'badge-warning';
                                                if ($refund['status'] === 'approved') {
                                                    $statusClass = 'badge-success';
                                                } else if ($refund['status'] === 'rejected') {
                                                    $statusClass = 'badge-danger';
                                                } else if ($refund['status'] === 'processed') {
                                                    $statusClass = 'badge-info';
                                                }
                                                ?>                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php 
                                                    $refundStatusIcon = '<i class="fas fa-clock"></i> ';
                                                    if ($refund['status'] === 'approved') {
                                                        $refundStatusIcon = '<i class="fas fa-check-circle"></i> ';
                                                    } else if ($refund['status'] === 'rejected') {
                                                        $refundStatusIcon = '<i class="fas fa-times-circle"></i> ';
                                                    } else if ($refund['status'] === 'processed') {
                                                        $refundStatusIcon = '<i class="fas fa-check-double"></i> ';
                                                    }
                                                    echo $refundStatusIcon . ucfirst($refund['status']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($refund['processed_date'])) {
                                                    echo date('M j, Y', strtotime($refund['processed_date']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>                    <div class="no-refunds-message">
                        <p>You haven't made any refund requests yet.</p>
                        <p class="text-muted">You can request a refund from the Payment History tab if eligible.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal" id="paymentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-credit-card"></i> Make Payment</h4>
                <button type="button" class="close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" method="POST" action="billing.php?tab=bills">
                    <input type="hidden" name="action" value="make_payment">
                    <input type="hidden" name="bill_id" id="bill_id" value="">
                    
                    <div class="payment-summary">
                        <div class="payment-summary-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="payment-summary-details">
                            <p class="text-primary mb-1">Payment Summary</p>
                            <p class="text-muted">Please review your payment details below</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount to Pay (RM):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">RM</span>
                            </div>
                            <input type="number" class="form-control" id="amount" name="amount" required step="0.01" min="0.01">
                        </div>
                        <small class="form-text text-muted">The remaining amount due is RM <span id="remainingAmount">0.00</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="">-- Select Payment Method --</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_number">Reference Number:</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Transaction reference, if applicable">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information about this payment"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitPayment()"><i class="fas fa-check"></i> Submit Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal" id="refundModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-undo-alt"></i> Request Refund</h4>
                <button type="button" class="close" onclick="closeRefundModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="refundForm" method="POST" action="billing.php?tab=refunds">
                    <input type="hidden" name="action" value="request_refund">
                    <input type="hidden" name="payment_id" id="refund_payment_id" value="">
                    
                    <div class="refund-info">
                        <div class="refund-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="refund-text">
                            <p>Refund requests are subject to approval by the hostel administration. 
                            You will be notified once your request has been processed.</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_amount">Refund Amount (RM):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">RM</span>
                            </div>
                            <input type="number" class="form-control" id="refund_amount" name="refund_amount" required step="0.01" min="0.01">
                        </div>
                        <small class="form-text text-muted">Maximum refund amount is RM <span id="maxRefundAmount">0.00</span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_reason">Reason for Refund:</label>
                        <textarea class="form-control" id="refund_reason" name="refund_reason" rows="4" required placeholder="Please explain why you are requesting a refund"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRefundModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitRefund()"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice View Modal -->
<div class="modal" id="invoiceModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-file-invoice"></i> Invoice</h4>
                <button type="button" class="close" onclick="closeInvoiceModal()">&times;</button>
            </div>
            <div class="modal-body" id="invoiceContent">
                <!-- Invoice content will be dynamically inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeInvoiceModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="printInvoiceModal()">
                    <i class="fas fa-print"></i> Print Invoice
                </button>                <button type="button" class="btn btn-success" onclick="downloadInvoicePDF()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Payment Modal Functions
function showPaymentModal(billId, remainingAmount) {
    document.getElementById('bill_id').value = billId;
    document.getElementById('remainingAmount').textContent = remainingAmount.toFixed(2);
    document.getElementById('amount').value = remainingAmount.toFixed(2);
    document.getElementById('amount').max = remainingAmount;
    
    // Show the modal
    document.getElementById('paymentModal').style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

function submitPayment() {
    document.getElementById('paymentForm').submit();
}

// Refund Modal Functions
function showRefundModal(paymentId, maxAmount) {
    document.getElementById('refund_payment_id').value = paymentId;
    document.getElementById('maxRefundAmount').textContent = maxAmount.toFixed(2);
    document.getElementById('refund_amount').value = maxAmount.toFixed(2);
    document.getElementById('refund_amount').max = maxAmount;
    
    // Show the modal
    document.getElementById('refundModal').style.display = 'block';
}

function closeRefundModal() {
    document.getElementById('refundModal').style.display = 'none';
}

function submitRefund() {
    document.getElementById('refundForm').submit();
}

// Global variable to store the current invoice being viewed
let currentInvoice = null;

// Invoice Functions
function viewInvoice(invoice) {
    // Store the invoice in the global variable
    currentInvoice = invoice;
    const studentName = "<?php echo $_SESSION["fullname"] ?? $_SESSION["user"]; ?>";
    const today = new Date();
    const formattedDate = today.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const paymentDate = new Date(invoice.payment_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const generatedDate = new Date(invoice.generated_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    
    const content = `
        <div class="invoice-container">
            <div class="invoice-details">
                <div class="invoice-header">
                    <div>
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">#${invoice.invoice_number}</div>
                    </div>
                    <div>
                        <div class="invoice-date">Date: ${generatedDate}</div>
                        <div class="invoice-status">Status: <span class="badge badge-success">Paid</span></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col">
                        <h4>Billed To:</h4>
                        <div class="billed-to">
                            <p><strong>${studentName}</strong></p>
                            <p>Student ID: <?php echo $username; ?></p>
                            <p>Multimedia University</p>
                            <p>Cyberjaya, Selangor, Malaysia</p>
                        </div>
                    </div>
                    <div class="col">
                        <h4>From:</h4>
                        <div class="billed-from">
                            <p><strong>MMU Hostel Management</strong></p>
                            <p>Multimedia University</p>
                            <p>Jalan Multimedia, 63100</p>
                            <p>Cyberjaya, Selangor, Malaysia</p>
                        </div>
                    </div>
                </div>
                
                <div class="invoice-items-container">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Semester</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Hostel Accommodation Fee</strong><br>
                                    <small class="text-muted">Payment for student housing</small>
                                </td>
                                <td>${invoice.semester} ${invoice.academic_year}</td>
                                <td class="text-right">RM ${parseFloat(invoice.amount).toFixed(2)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="invoice-summary">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="payment-info">
                                <h4>Payment Information:</h4>
                                <table class="payment-info-table">
                                    <tr>
                                        <td><strong>Method:</strong></td>
                                        <td>${invoice.payment_method.replace(/_/g, ' ').toUpperCase()}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Reference:</strong></td>
                                        <td>${invoice.reference_number || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td>${paymentDate}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="invoice-totals">
                                <div class="invoice-total-row">
                                    <div>Subtotal:</div>
                                    <div>RM ${parseFloat(invoice.amount).toFixed(2)}</div>
                                </div>
                                <div class="invoice-total-row">
                                    <div>Tax:</div>
                                    <div>RM 0.00</div>
                                </div>
                                <div class="invoice-total-row total">
                                    <div>Total:</div>
                                    <div class="invoice-total-amount">RM ${parseFloat(invoice.amount).toFixed(2)}</div>
                                </div>
                                <div class="invoice-total-row paid">
                                    <div>Amount Paid:</div>
                                    <div>RM ${parseFloat(invoice.amount).toFixed(2)}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="invoice-footer">
                    <p><strong>Notes:</strong></p>
                    <p>This is an official receipt of your payment. Thank you for your prompt payment.</p>
                    <p>For any inquiries, please contact the hostel office at <a href="mailto:hostel@mmu.edu.my">hostel@mmu.edu.my</a> or call +603-8312-5555.</p>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('invoiceContent').innerHTML = content;
    document.getElementById('invoiceModal').style.display = 'block';
}

function closeInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}

function printInvoiceModal() {
    const content = document.getElementById('invoiceContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Invoice</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px;
                    color: #333;
                    line-height: 1.5;
                }
                .invoice-container { 
                    max-width: 800px; 
                    margin: 0 auto; 
                    background: #fff;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    border-radius: 5px;
                }
                .invoice-details {
                    padding: 30px;
                }
                .invoice-header { 
                    display: flex; 
                    justify-content: space-between; 
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #4e73df;
                }
                .invoice-title { 
                    font-size: 28px; 
                    font-weight: bold;
                    color: #4e73df;
                    letter-spacing: 1px;
                }
                .invoice-number { 
                    font-size: 16px;
                    color: #666;
                    margin-top: 5px;
                }
                .invoice-date, .invoice-status { 
                    color: #666;
                    margin-bottom: 5px;
                    text-align: right;
                }
                .badge {
                    padding: 5px 10px;
                    border-radius: 30px;
                    font-size: 12px;
                    font-weight: bold;
                    color: white;
                    background-color: #1cc88a;
                }
                .row { 
                    display: flex; 
                    margin: 0 -15px; 
                }
                .col { 
                    flex: 1; 
                    padding: 0 15px; 
                }
                h4 { 
                    margin: 0 0 15px 0;
                    color: #4e73df;
                    font-weight: 600;
                    font-size: 16px;
                }
                p { 
                    margin: 5px 0; 
                }
                .billed-to, .billed-from {
                    padding: 10px 0;
                }
                .invoice-items-container {
                    margin: 30px 0;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 20px 0; 
                }
                table th { 
                    padding: 12px 10px; 
                    text-align: left; 
                    border-bottom: 2px solid #e3e6f0; 
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    color: #4e73df;
                }
                table td { 
                    padding: 12px 10px; 
                    border-bottom: 1px solid #e3e6f0; 
                }
                .text-right {
                    text-align: right;
                }
                .text-muted {
                    color: #858796;
                    font-size: 13px;
                }
                .invoice-summary {
                    margin-top: 30px;
                }
                .payment-info {
                    background: #f8f9fc;
                    padding: 15px;
                    border-radius: 5px;
                }
                .payment-info-table {
                    margin: 10px 0;
                }
                .payment-info-table td {
                    padding: 5px 15px 5px 0;
                    border: none;
                }
                .invoice-totals {
                    background: #f8f9fc;
                    padding: 15px;
                    border-radius: 5px;
                }
                .invoice-total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 5px 0;
                }
                .invoice-total-row.total {
                    font-weight: bold;
                    font-size: 16px;
                    border-top: 1px solid #e3e6f0;
                    border-bottom: 1px solid #e3e6f0;
                    margin: 5px 0;
                    padding: 10px 0;
                }
                .invoice-total-row.paid {
                    color: #1cc88a;
                    font-weight: bold;
                }
                .invoice-total-amount {
                    font-size: 16px;
                }
                .invoice-footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px dashed #e3e6f0;
                    font-size: 14px;
                }
                a {
                    color: #4e73df;
                    text-decoration: none;
                }
                .col-md-5 {
                    flex: 0 0 41.6%;
                    max-width: 41.6%;
                }
                .col-md-7 {
                    flex: 0 0 58.3%;
                    max-width: 58.3%;
                }
                @media print {
                    .invoice-container {
                        box-shadow: none;
                    }
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

function printInvoice(invoice) {
    viewInvoice(invoice);
    setTimeout(() => {
        printInvoiceModal();
    }, 500);
}

function downloadInvoicePDF() {
    if (!currentInvoice) {
        console.error("No invoice is currently being viewed");
        return;
    }
    
    const studentName = "<?php echo $_SESSION["fullname"] ?? $_SESSION["user"]; ?>";
    const studentId = "<?php echo $username; ?>";
    
    // Use the PDFGenerator class
    PDFGenerator.generateInvoicePDF(currentInvoice, studentName, studentId);
}

// Tab Navigation
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.nav-link');
    const tabContents = document.querySelectorAll('.tab-pane');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // This is handled by href="?tab=x" now, but keep it for any direct interactions
        });
    });
});
</script>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>