<?php
session_start();

// Check if user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION["role"] !== "student") {
    header("Location: ../student/login.php");
    exit;
}

// Include database connection
require_once '../shared/includes/db_connection.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../student/my_registrations.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$response = [
    'success' => false,
    'message' => 'Unknown error occurred'
];

// Validate inputs
if (!isset($_POST['reg_id']) || !is_numeric($_POST['reg_id'])) {
    $response['message'] = "Invalid registration ID";
} else {
    $reg_id = (int)$_POST['reg_id'];
    
    // Verify registration belongs to the logged-in student and is in "Approved" status with "Unpaid" payment status
    $stmt = $conn->prepare("
        SELECT hr.*, r.price as rate_per_semester, r.room_number
        FROM hostel_registrations hr
        JOIN rooms r ON hr.room_id = r.id
        WHERE hr.id = ? AND hr.student_id = ? AND hr.status = 'Approved' AND hr.payment_status = 'Unpaid'
    ");
    $stmt->bind_param("ii", $reg_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = "Registration not found or not eligible for payment";
    } else {
        $registration_data = $result->fetch_assoc();
        $amount = $registration_data['rate_per_semester'];
        $reference_number = 'PAY-' . time() . '-' . $reg_id;
        $room_id = $registration_data['room_id'];
        $room_number = $registration_data['room_number'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create a bill for this room registration payment
            // This ensures we have a valid bill_id to use in the payments table
            $current_semester = "Current"; // Or retrieve from system settings
            $current_academic_year = date('Y') . '/' . (date('Y') + 1);
            $due_date = date('Y-m-d'); // Today's date since payment is being made now
            
            $bill_stmt = $conn->prepare("
                INSERT INTO bills (student_id, room_id, semester, academic_year, amount, due_date, status)
                VALUES (?, ?, ?, ?, ?, ?, 'paid')
            ");
            $bill_stmt->bind_param("iissds", $student_id, $room_id, $current_semester, $current_academic_year, $amount, $due_date);
            $bill_stmt->execute();
            $bill_id = $conn->insert_id;
            $bill_stmt->close();
            
            // Update registration payment status
            $update_stmt = $conn->prepare("
                UPDATE hostel_registrations 
                SET payment_status = 'Paid', 
                    paid_amount = ?, 
                    total_amount = ?
                WHERE id = ? AND student_id = ?
            ");
            $update_stmt->bind_param("ddii", $amount, $amount, $reg_id, $student_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Add payment record with valid bill_id
            $payment_stmt = $conn->prepare("
                INSERT INTO payments (bill_id, student_id, amount, payment_method, reference_number, status, notes)
                VALUES (?, ?, ?, ?, ?, 'completed', 'Room registration payment for Room " . $room_number . "')
            ");
            $payment_method = $_POST['payment_method'];
            $payment_stmt->bind_param("iidss", $bill_id, $student_id, $amount, $payment_method, $reference_number);
            $payment_stmt->execute();
            $payment_id = $conn->insert_id;
            $payment_stmt->close();
            
            // Create invoice record
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
            
            $response['success'] = true;
            $response['message'] = "Payment processed successfully!";
            $response['reference'] = $reference_number;
            $response['invoice'] = $invoice_number;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $response['message'] = "Payment processing error: " . $e->getMessage();
        }
    }
    $stmt->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
