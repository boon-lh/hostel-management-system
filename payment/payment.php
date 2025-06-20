<?php
session_start();

// Check if user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION["role"] !== "student") {
    header("Location: ../student/login.php");
    exit;
}

// Include database connection
require_once '../shared/includes/db_connection.php';

$student_id = $_SESSION['user_id'];
$error = '';
$payment_successful = false;
$registration_data = [];

// Validate registration ID
if (!isset($_GET['reg_id']) || !is_numeric($_GET['reg_id'])) {
    $error = "Invalid registration ID provided.";
} else {
    $reg_id = $_GET['reg_id'];
    
    // Verify registration belongs to the logged-in student and is in "Approved" status with "Unpaid" payment status
    $stmt = $conn->prepare("
        SELECT hr.*, r.room_number, r.type as room_type, r.price as rate_per_semester, hb.block_name,
        s.name AS student_name, s.contact_no, s.email
        FROM hostel_registrations hr
        JOIN rooms r ON hr.room_id = r.id
        JOIN hostel_blocks hb ON r.block_id = hb.id
        JOIN students s ON hr.student_id = s.id
        WHERE hr.id = ? AND hr.student_id = ? AND hr.status = 'Approved' AND hr.payment_status = 'Unpaid'
    ");
    $stmt->bind_param("ii", $reg_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Registration not found or not eligible for payment.";
    } else {
        $registration_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment']) && empty($error)) {
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $card_number = isset($_POST['card_number']) ? $conn->real_escape_string($_POST['card_number']) : '';
    $card_name = isset($_POST['card_name']) ? $conn->real_escape_string($_POST['card_name']) : '';
    $expiry_date = isset($_POST['expiry_date']) ? $conn->real_escape_string($_POST['expiry_date']) : '';
    $cvv = isset($_POST['cvv']) ? $conn->real_escape_string($_POST['cvv']) : '';
    $amount = $registration_data['rate_per_semester'];
    $reference_number = 'PAY-' . time() . '-' . $reg_id;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Create payment record
        $payment_stmt = $conn->prepare("
            INSERT INTO payments (bill_id, student_id, amount, payment_method, reference_number, status, notes)
            VALUES (?, ?, ?, ?, ?, 'completed', 'Room registration payment')
        ");
        
        // Using 0 as bill_id since we're directly paying for registration, not a bill
        $bill_id = 0;
        $payment_stmt->bind_param("iidss", $bill_id, $student_id, $amount, $payment_method, $reference_number);
        $payment_stmt->execute();
        $payment_id = $conn->insert_id;
        $payment_stmt->close();
        
        // 2. Update hostel registration payment status
        $update_reg_stmt = $conn->prepare("
            UPDATE hostel_registrations 
            SET payment_status = 'Paid', paid_amount = total_amount 
            WHERE id = ? AND student_id = ?
        ");
        $update_reg_stmt->bind_param("ii", $reg_id, $student_id);
        $update_reg_stmt->execute();
        $update_reg_stmt->close();
          // 3. Create invoice record
        $invoice_number = 'INV-' . date('Ymd') . '-' . $reg_id;
        $invoice_stmt = $conn->prepare("
            INSERT INTO invoices (invoice_number, payment_id, student_id)
            VALUES (?, ?, ?)
        ");
        $invoice_stmt->bind_param("sii", $invoice_number, $payment_id, $student_id);
        $invoice_stmt->execute();
        $invoice_stmt->close();
        
        // Commit transaction
        $conn->commit();
        $payment_successful = true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Payment processing error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Payment - MMU Hostel Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f4f7fa;
            padding-top: 20px;
        }
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .payment-header h2 {
            color: #3498db;
            font-weight: bold;
        }
        .payment-form {
            padding: 20px;
            border-top: 1px solid #eee;
        }
        .room-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .payment-success {
            text-align: center;
            padding: 30px 20px;
        }
        .payment-success i {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .form-check-label {
            margin-left: 5px;
        }
        .btn-pay {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            font-weight: bold;
        }
        .btn-pay:hover {
            background-color: #2980b9;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2><i class="fas fa-money-check-alt"></i> Room Payment</h2>
                <p>MMU Hostel Management System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo $error; ?>
                    <p class="mt-3">
                        <a href="../student/my_registrations.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> Return to My Registrations
                        </a>
                    </p>
                </div>
            <?php elseif ($payment_successful): ?>
                <div class="payment-success">
                    <i class="fas fa-check-circle"></i>
                    <h3>Payment Successful!</h3>
                    <p>Your payment for room registration has been processed successfully.</p>
                    <p>Reference Number: <strong><?php echo $reference_number; ?></strong></p>
                    <p>Amount Paid: <strong>RM <?php echo number_format($registration_data['rate_per_semester'], 2); ?></strong></p>
                    <p>You will receive a confirmation receipt via email shortly.</p>
                    <a href="../student/my_registrations.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home"></i> Return to My Registrations
                    </a>
                </div>
            <?php else: ?>
                <!-- Room and Registration Information -->
                <div class="room-info">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Registration Details</h5>
                            <p><strong>Registration ID:</strong> <?php echo $registration_data['id']; ?></p>
                            <p><strong>Registration Date:</strong> <?php echo date('d M Y', strtotime($registration_data['registration_date'])); ?></p>
                            <p><strong>Check-in Date:</strong> <?php echo date('d M Y', strtotime($registration_data['approved_check_in_date'])); ?></p>
                            <p><strong>Check-out Date:</strong> <?php echo date('d M Y', strtotime($registration_data['approved_check_out_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Room Information</h5>
                            <p><strong>Block:</strong> <?php echo $registration_data['block_name']; ?></p>
                            <p><strong>Room Number:</strong> <?php echo $registration_data['room_number']; ?></p>
                            <p><strong>Room Type:</strong> <?php echo $registration_data['room_type']; ?></p>
                            <p><strong>Amount Due:</strong> <span class="text-danger font-weight-bold">RM <?php echo number_format($registration_data['rate_per_semester'], 2); ?></span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <div class="payment-form">
                    <h4>Payment Method</h4>
                    <form method="POST" action="">
                        <div class="form-group">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                <label class="form-check-label" for="credit_card">
                                    <i class="fas fa-credit-card"></i> Credit Card
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                <label class="form-check-label" for="bank_transfer">
                                    <i class="fas fa-university"></i> Bank Transfer
                                </label>
                            </div>
                        </div>
                        
                        <!-- Credit Card Details -->
                        <div id="credit_card_details">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="card_name">Name on Card</label>
                                    <input type="text" class="form-control" id="card_name" name="card_name" placeholder="John Doe" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="cvv">CVV</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bank Transfer Details -->
                        <div id="bank_transfer_details" style="display: none;">
                            <div class="alert alert-info">
                                <h5>Bank Account Details</h5>
                                <p><strong>Bank:</strong> My Bank Malaysia</p>
                                <p><strong>Account Number:</strong> 1234-5678-9012</p>
                                <p><strong>Account Name:</strong> MMU Hostel Management</p>
                                <p><strong>Reference:</strong> Please include your Student ID and Registration ID as reference</p>
                                <p class="mt-3 mb-0">After making the transfer, click the Pay Now button to complete your registration.</p>
                            </div>
                        </div>
                        
                        <div class="form-group form-check mt-4">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">I agree to the terms and conditions for hostel accommodation</label>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="../student/my_registrations.php" class="btn btn-secondary mr-2">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" name="submit_payment" class="btn btn-pay">
                                <i class="fas fa-lock"></i> Pay Now - RM <?php echo number_format($registration_data['rate_per_semester'], 2); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap & jQuery Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle between payment methods
            $('input[name="payment_method"]').change(function() {
                if ($(this).val() === 'credit_card') {
                    $('#credit_card_details').show();
                    $('#bank_transfer_details').hide();
                } else {
                    $('#credit_card_details').hide();
                    $('#bank_transfer_details').show();
                }
            });
            
            // Format card number
            $('#card_number').on('input', function() {
                let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                $(this).val(formattedValue);
            });
            
            // Format expiry date
            $('#expiry_date').on('input', function() {
                let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                
                if (value.length > 2) {
                    $(this).val(value.substring(0, 2) + '/' + value.substring(2, 4));
                } else {
                    $(this).val(value);
                }
            });
        });
    </script>
</body>
</html>
