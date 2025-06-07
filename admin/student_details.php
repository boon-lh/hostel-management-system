<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Student ID is required.";
    header("Location: students.php");
    exit();
}

// Validate student ID is numeric
$studentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($studentId === false || $studentId === null) {
    $_SESSION['error'] = "Invalid student ID format.";
    header("Location: students.php");
    exit();
}

try {
    // Fetch student details
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Student not found.";
        header("Location: students.php");
        exit();
    }

    $student = $result->fetch_assoc();

    // Fetch emergency contact information
    $emergency_query = "SELECT * FROM emergency_contacts WHERE student_id = ?";
    $stmt = $conn->prepare($emergency_query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $emergency_result = $stmt->get_result();
    $emergency_contact = $emergency_result->fetch_assoc();

    // Fetch hostel registration information
    $hostel_query = "SELECT hr.*, r.room_number, hb.block_name, r.type as room_type
                     FROM hostel_registrations hr
                     LEFT JOIN rooms r ON hr.room_id = r.id
                     LEFT JOIN hostel_blocks hb ON r.block_id = hb.id
                     WHERE hr.student_id = ? 
                     ORDER BY hr.registration_date DESC
                     LIMIT 1";
    $stmt = $conn->prepare($hostel_query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $hostel_result = $stmt->get_result();
    $hostel_info = $hostel_result->fetch_assoc();

    // Fetch financial summary
    $finance_query = "SELECT 
                        COUNT(b.id) as total_bills,
                        COALESCE(SUM(b.amount), 0) as total_billed,
                        COALESCE(SUM(p.amount), 0) as total_paid,
                        COALESCE(SUM(b.amount), 0) - COALESCE(SUM(p.amount), 0) as outstanding_balance
                     FROM bills b
                     LEFT JOIN payments p ON b.id = p.bill_id AND p.status = 'completed'
                     WHERE b.student_id = ?";
    $stmt = $conn->prepare($finance_query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $finance_result = $stmt->get_result();
    $finance_summary = $finance_result->fetch_assoc();

    // Fetch complaints summary
    $complaints_query = "SELECT COUNT(*) as complaint_count,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints
                        FROM complaints WHERE student_id = ?";
    $stmt = $conn->prepare($complaints_query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $complaints_result = $stmt->get_result();
    $complaints_summary = $complaints_result->fetch_assoc();

} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching student details: " . $e->getMessage();
    header("Location: students.php");
    exit();
}

// Set page title and additional CSS/JS files
$pageTitle = "Student Details - MMU Hostel Management";
$additionalCSS = ["css/dashboard.css", "css/student-management.css"];
$additionalJS = [
    "https://code.jquery.com/jquery-3.6.0.min.js",
    "https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js",
    "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
    "../shared/js/script.js",
    "js/students.js"
];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<div class="main-content">
    <?php 
    $pageHeading = "Student Details";
    require_once 'admin-content-header.php'; 
    ?>

    <div class="content-wrapper">
        <nav class="page-navigation">
            <a href="students.php" class="nav-tab">
                <i class="fas fa-users"></i>
                Student List
            </a>
            <a href="student_details.php?id=<?php echo $studentId; ?>" class="nav-tab active">
                <i class="fas fa-user"></i>
                Student Details
            </a>
        </nav>

        <div class="student-details-container">
            <!-- Student Header -->
            <div class="student-header-card">
                <div class="profile-section">
                    <div class="student-avatar">
                        <?php if (!empty($student['profile_pic'])): ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Student Photo">
                        <?php else: ?>
                            <div class="default-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="student-basic-info">
                        <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                        <p class="student-id">Student ID: <?php echo htmlspecialchars($student['id']); ?></p>
                        <p class="student-username">Username: <?php echo htmlspecialchars($student['username']); ?></p>
                        <div class="student-actions">
                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Details
                            </a>
                            <button type="button" class="btn btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo addslashes(htmlspecialchars($student['name'])); ?>')">
                                <i class="fas fa-trash-alt"></i> Delete Student
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="details-grid">
                <!-- Personal Information Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name:</label>
                                <span><?php echo htmlspecialchars($student['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?php echo htmlspecialchars($student['gender']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Date of Birth:</label>
                                <span><?php echo date('F j, Y', strtotime($student['dob'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>IC/Passport:</label>
                                <span><?php echo htmlspecialchars($student['ic_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Contact:</label>
                                <span><?php echo htmlspecialchars($student['contact_no']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($student['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Citizenship:</label>
                                <span><?php echo htmlspecialchars($student['citizenship']); ?></span>
                            </div>
                            <div class="info-item full-width">
                                <label>Address:</label>
                                <span><?php echo htmlspecialchars($student['address']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Information Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Course:</label>
                                <span><?php echo htmlspecialchars($student['course']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Registration Date:</label>
                                <span><?php echo date('F j, Y', strtotime($student['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Account Status:</label>
                                <span class="status status-active">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hostel Information Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><i class="fas fa-hotel"></i> Hostel Information</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($hostel_info): ?>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Room Number:</label>
                                    <span><?php echo htmlspecialchars($hostel_info['room_number'] ?? 'Not assigned'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Block:</label>
                                    <span><?php echo htmlspecialchars($hostel_info['block_name'] ?? 'Not assigned'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Room Type:</label>
                                    <span><?php echo htmlspecialchars($hostel_info['room_type'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Registration Date:</label>
                                    <span><?php echo date('F j, Y', strtotime($hostel_info['registration_date'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Status:</label>
                                    <span class="status <?php echo 'status-' . strtolower(str_replace(' ', '-', $hostel_info['status'])); ?>">
                                        <?php echo htmlspecialchars($hostel_info['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No hostel registration found for this student.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Emergency Contact Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($emergency_contact): ?>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Contact Name:</label>
                                    <span><?php echo htmlspecialchars($emergency_contact['name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Relationship:</label>
                                    <span><?php echo htmlspecialchars($emergency_contact['relationship']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>IC Number:</label>
                                    <span><?php echo htmlspecialchars($emergency_contact['ic_number']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Contact Number:</label>
                                    <span><?php echo htmlspecialchars($emergency_contact['contact_no']); ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Email:</label>
                                    <span><?php echo htmlspecialchars($emergency_contact['email']); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No emergency contact information found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Financial Summary Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Financial Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Total Bills:</label>
                                <span><?php echo $finance_summary['total_bills'] ?? 0; ?></span>
                            </div>
                            <div class="info-item">
                                <label>Total Amount Billed:</label>
                                <span>RM <?php echo number_format($finance_summary['total_billed'] ?? 0, 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Total Amount Paid:</label>
                                <span>RM <?php echo number_format($finance_summary['total_paid'] ?? 0, 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Outstanding Balance:</label>
                                <span class="<?php echo ($finance_summary['outstanding_balance'] > 0) ? 'text-danger' : ''; ?>">
                                    RM <?php echo number_format($finance_summary['outstanding_balance'] ?? 0, 2); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="finance.php?student_id=<?php echo $student['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-list"></i> View Billing History
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Complaints Summary Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-circle"></i> Complaints Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Total Complaints:</label>
                                <span><?php echo $complaints_summary['complaint_count'] ?? 0; ?></span>
                            </div>
                            <div class="info-item">
                                <label>Pending Complaints:</label>
                                <span class="<?php echo ($complaints_summary['pending_complaints'] > 0) ? 'text-warning' : ''; ?>">
                                    <?php echo $complaints_summary['pending_complaints'] ?? 0; ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="complaints.php?student_id=<?php echo $student['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-list"></i> View Complaints History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>
