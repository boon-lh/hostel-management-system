<?php
/**
 * Get Student Details - AJAX Endpoint
 * Hostel Management System - Admin Panel
 * 
 * This file handles AJAX requests to fetch detailed student information
 * for display in the student details modal.
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    echo '<div class="error-message">Access denied. Please login as admin.</div>';
    exit();
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo '<div class="error-message">Student ID is required.</div>';
    exit();
}

require_once '../shared/includes/db_connection.php';

$studentId = intval($_GET['id']);

try {
    // Fetch student details
    $student_query = "SELECT * FROM students WHERE id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        echo '<div class="error-message">Student not found.</div>';
        exit();
    }
    
    $student = $student_result->fetch_assoc();
    
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
    
    // Fetch recent complaints
    $complaints_query = "SELECT COUNT(*) as complaint_count,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints
                        FROM complaints WHERE student_id = ?";
    $stmt = $conn->prepare($complaints_query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $complaints_result = $stmt->get_result();
    $complaints_summary = $complaints_result->fetch_assoc();
    
} catch (Exception $e) {
    echo '<div class="error-message">Error fetching student details: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit();
}
?>

<div class="student-details-container">
    <div class="student-header">
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
        </div>
    </div>
    
    <div class="student-details-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="personal">Personal Info</button>
            <button class="tab-btn" data-tab="academic">Academic Info</button>
            <button class="tab-btn" data-tab="hostel">Hostel Info</button>
            <button class="tab-btn" data-tab="emergency">Emergency Contact</button>
            <button class="tab-btn" data-tab="summary">Summary</button>
        </div>
        
        <!-- Personal Information Tab -->
        <div class="tab-content active" id="personal">
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
                    <label>IC Number:</label>
                    <span><?php echo htmlspecialchars($student['ic_number']); ?></span>
                </div>
                <div class="info-item">
                    <label>Contact Number:</label>
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
        
        <!-- Academic Information Tab -->
        <div class="tab-content" id="academic">
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
        
        <!-- Hostel Information Tab -->
        <div class="tab-content" id="hostel">
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
        
        <!-- Emergency Contact Tab -->
        <div class="tab-content" id="emergency">
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
        
        <!-- Summary Tab -->
        <div class="tab-content" id="summary">
            <div class="summary-cards">
                <div class="summary-card finance">
                    <div class="card-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="card-content">
                        <h4>Financial Summary</h4>
                        <div class="summary-stats">
                            <div class="stat">
                                <span class="label">Total Bills:</span>
                                <span class="value"><?php echo $finance_summary['total_bills'] ?? 0; ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Total Billed:</span>
                                <span class="value">RM <?php echo number_format($finance_summary['total_billed'] ?? 0, 2); ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Total Paid:</span>
                                <span class="value">RM <?php echo number_format($finance_summary['total_paid'] ?? 0, 2); ?></span>
                            </div>
                            <div class="stat outstanding">
                                <span class="label">Outstanding:</span>
                                <span class="value">RM <?php echo number_format($finance_summary['outstanding_balance'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card complaints">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-content">
                        <h4>Complaints Summary</h4>
                        <div class="summary-stats">
                            <div class="stat">
                                <span class="label">Total Complaints:</span>
                                <span class="value"><?php echo $complaints_summary['complaint_count'] ?? 0; ?></span>
                            </div>
                            <div class="stat">
                                <span class="label">Pending:</span>
                                <span class="value"><?php echo $complaints_summary['pending_complaints'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-actions">
        <button type="button" class="btn btn-primary" onclick="editStudent(<?php echo $student['id']; ?>)">
            <i class="fas fa-edit"></i> Edit Student
        </button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('student-details-modal').style.display='none'">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
</div>

<style>
.student-details-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.student-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e3e6f0;
}

.student-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 20px;
    border: 3px solid #4e73df;
}

.student-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.default-avatar {
    width: 100%;
    height: 100%;
    background: #f8f9fc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #858796;
    font-size: 2rem;
}

.student-basic-info h2 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.student-id, .student-username {
    margin: 2px 0;
    color: #7f8c8d;
    font-size: 14px;
}

.tab-buttons {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #e3e6f0;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    color: #858796;
    font-weight: 500;
    transition: all 0.3s ease;
}

.tab-btn.active {
    color: #4e73df;
    border-bottom-color: #4e73df;
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 14px;
}

.info-item span {
    color: #7f8c8d;
    padding: 8px 0;
}

.status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.status-active { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-inactive { background: #f8d7da; color: #721c24; }
.status-checked-in { background: #d4edda; color: #155724; }
.status-checked-out { background: #f8d7da; color: #721c24; }

.no-data {
    text-align: center;
    padding: 40px;
    color: #858796;
}

.summary-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.summary-card {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
}

.summary-card .card-icon {
    font-size: 2rem;
    margin-bottom: 15px;
}

.finance .card-icon { color: #1cc88a; }
.complaints .card-icon { color: #f6c23e; }

.summary-card h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.summary-stats .stat {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fc;
}

.summary-stats .stat:last-child {
    border-bottom: none;
}

.summary-stats .stat.outstanding {
    font-weight: 600;
    color: #e74a3b;
}

.modal-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e3e6f0;
    text-align: right;
}

.modal-actions .btn {
    margin-left: 10px;
    padding: 8px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4e73df;
    color: white;
}

.btn-primary:hover {
    background: #2e59d9;
}

.btn-secondary {
    background: #858796;
    color: white;
}

.btn-secondary:hover {
    background: #5a5c69;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .student-header {
        flex-direction: column;
        text-align: center;
    }
    
    .student-avatar {
        margin-bottom: 15px;
        margin-right: 0;
    }
}
</style>

<script>
// Tab switching functionality for the modal
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all tabs and content
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Show corresponding content
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');
    });
});
</script>
