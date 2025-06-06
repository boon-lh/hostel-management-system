<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

// Get all students
$students_query = "SELECT * FROM students ORDER BY id ASC";
$students_result = $conn->query($students_query);
$students = [];
if ($students_result && $students_result->num_rows > 0) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get financial information (bills, payments, and outstanding balances)
$finance_query = "
    SELECT 
        s.id as student_id,
        s.name as student_name,
        b.semester,
        b.academic_year,
        b.amount as bill_amount,
        b.due_date,
        b.status as bill_status,
        COALESCE(SUM(p.amount), 0) as paid_amount,
        b.amount - COALESCE(SUM(p.amount), 0) as balance
    FROM 
        students s
    LEFT JOIN 
        bills b ON s.id = b.student_id
    LEFT JOIN 
        payments p ON b.id = p.bill_id AND p.status = 'completed'
    GROUP BY 
        s.id, b.id
    ORDER BY 
        s.id ASC, b.due_date DESC
";
$finance_result = $conn->query($finance_query);
$finance_data = [];
if ($finance_result && $finance_result->num_rows > 0) {
    while ($row = $finance_result->fetch_assoc()) {
        $finance_data[] = $row;
    }
}

// Set page title and additional CSS files
$pageTitle = "Student Information - MMU Hostel Management";
$additionalCSS = ["css/dashboard.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';
?>

<!-- Main Content -->
<style>
/* Modern UI Styles for Student Management */
.main-content {
    padding: 25px;
    background-color: #f8f9fc;
    min-height: 100vh;
}

.profile-wrapper {
    position: relative;
}

.profile-image-container {
    position: relative;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    padding: 2px;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.profile-image-container:hover {
    transform: scale(1.05);
}

.profile-image {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
}

.profile-status {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    background-color: #2ecc71;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-status.online { background-color: #2ecc71; }
.profile-status.away { background-color: #f1c40f; }
.profile-status.busy { background-color: #e74c3c; }

.user-details {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.user-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.95rem;
}

.user-role {
    font-size: 0.8rem;
    color: #7f8c8d;
    font-weight: 500;
}

.logout-btn {
    color: #e74c3c;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: rgba(231, 76, 60, 0.1);
}

.logout-btn:hover {
    color: #fff;
    background: #e74c3c;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2);
}

.logout-btn:hover {
    color: #c0392b;
}

/* Tab Navigation */
.tab-navigation {
    background: #fff;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 25px;
}

.tab-button {
    padding: 10px 20px;
    border: none;
    background: none;
    color: #7f8c8d;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    border-radius: 8px;
}

.tab-button:hover {
    color: #2c3e50;
    background: #f7f9fc;
}

.tab-button.active {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: #fff;
}

/* Card Styles */
.card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 25px;
    overflow: hidden;
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.card-title-area {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.card-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.card-title {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
    font-weight: 600;
}

.card-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.search-container {
    position: relative;
    flex: 1;
    max-width: 300px;
}

.search-container input {
    width: 100%;
    padding: 10px 15px;
    padding-right: 40px;
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.search-container input:focus {
    outline: none;
    border-color: #6e8efb;
    box-shadow: 0 0 0 3px rgba(110, 142, 251, 0.1);
}

.search-container i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
}

.btn-export {
    padding: 10px 20px;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(110, 142, 251, 0.3);
}

/* Table Styles */
.table-responsive {
    padding: 20px;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table th {
    background: #f8f9fc;
    padding: 12px;
    color: #2c3e50;
    font-weight: 600;
    text-align: left;
    border-bottom: 2px solid #e0e6ed;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #e0e6ed;
    color: #7f8c8d;
}

.data-table tbody tr:hover {
    background: #f8f9fc;
}

.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

.action-buttons a {
    color: #6e8efb;
    transition: color 0.3s;
}

.action-buttons a:hover {
    color: #a777e3;
}

/* Status Badges */
.status {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.status-paid { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-inactive { background: #f8d7da; color: #721c24; }
.status-overdue { background: #fde3e3; color: #a42a2a; }

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    animation: fadeIn 0.3s;
}

.modal-content {
    position: relative;
    background: #fff;
    width: 90%;
    max-width: 800px;
    margin: 50px auto;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s;
}

.close {
    position: absolute;
    right: 25px;
    top: 25px;
    font-size: 24px;
    color: #95a5a6;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover {
    color: #2c3e50;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Responsive Design */
@media (max-width: 991px) {
    .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .card-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .search-container {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .tab-navigation {
        overflow-x: auto;
        white-space: nowrap;
        padding: 10px;
    }

    .tab-button {
        padding: 8px 15px;
        font-size: 14px;
    }

    .data-table {
        display: block;
        overflow-x: auto;
    }

    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
}
</style>
<div class="main-content">
    <?php 
    $pageHeading = "Student Information";
    require_once '../shared/includes/admin-content-header.php'; 
    ?>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-button active" data-tab="student-list">All Students</button>
        <button class="tab-button" data-tab="finance-info">Finance Information</button>
    </div>

    <!-- Student List Tab (Default Active) -->
    <div class="tab-content active" id="student-list">
        <div class="card">            <div class="card-header">
                <div class="card-title-area">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="card-title">All Student List</h2>
                </div>
                <div class="card-actions">
                    <div class="search-container">
                        <input type="text" id="student-search" placeholder="Search by ID, name, course or email...">
                        <i class="fas fa-search"></i>
                    </div>
                    <button class="btn-export" title="Export to CSV">
                        <i class="fas fa-file-export"></i>
                        <span>Export List</span>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">                    <table class="data-table" id="students-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Citizenship</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['contact_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($student['citizenship']); ?></td>                        <td class="action-buttons">
                            <a href="javascript:void(0)" onclick="viewStudentDetails(<?php echo $student['id']; ?>)" title="View Student Details" class="action-btn">
                                <i class="fas fa-eye"></i>
                                <span class="action-text">Details</span>
                            </a>
                            <a href="javascript:void(0)" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student Information" class="action-btn">
                                <i class="fas fa-edit"></i>
                                <span class="action-text">Edit</span>
                            </a>
                            <a href="javascript:void(0)" onclick="viewFinance(<?php echo $student['id']; ?>)" title="View Financial Records" class="action-btn">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span class="action-text">Finance</span>
                            </a>
                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No students found in the database</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Finance Info Tab -->
    <div class="tab-content" id="finance-info">
        <!-- Finance information content -->
        <div class="card">
            <div class="card-header">
                <div class="card-title-area">
                    <div class="card-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h2 class="card-title">Student Financial Overview</h2>
                </div>
                <div class="card-actions">
                    <div class="search-container">
                        <input type="text" id="finance-search" placeholder="Search by Student ID...">
                        <i class="fas fa-search"></i>
                    </div>
                    <button class="btn-export"><i class="fas fa-file-export"></i> Export</button>
                </div>
            </div>
            <div class="card-content">
                <!-- Finance table content -->
                <!-- This would be populated from your database -->                <div class="table-responsive">
                    <table class="data-table" id="finance-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Due Date</th>
                                <th>Total Fee (RM)</th>
                                <th>Paid Amount (RM)</th>
                                <th>Balance (RM)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($finance_data) > 0): ?>
                                <?php foreach ($finance_data as $finance): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($finance['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($finance['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($finance['semester'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($finance['academic_year'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($finance['due_date'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($finance['bill_amount'] ?? 0, 2); ?></td>
                                        <td><?php echo number_format($finance['paid_amount'] ?? 0, 2); ?></td>
                                        <td><?php echo number_format($finance['balance'] ?? 0, 2); ?></td>
                                        <td>
                                            <?php 
                                            $status = $finance['bill_status'] ?? 'unknown';
                                            $statusClass = '';
                                            
                                            switch ($status) {
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
                                                default:
                                                    $statusClass = 'status-inactive';
                                            }
                                            ?>
                                            <span class="status <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                        </td>                                        <td class="action-buttons">
                                            <a href="javascript:void(0)" onclick="viewBillDetails(<?php echo $finance['student_id']; ?>)" title="View Bill Details" class="action-btn">
                                                <i class="fas fa-eye"></i>
                                                <span class="action-text">Bill Details</span>
                                            </a>
                                            <a href="javascript:void(0)" onclick="viewPaymentReceipt(<?php echo $finance['student_id']; ?>)" title="View Receipt" class="action-btn">
                                                <i class="fas fa-receipt"></i>
                                                <span class="action-text">Receipt</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">No financial information found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div id="student-details-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="student-details-content">
            <!-- Student details will be loaded here via AJAX -->
        </div>
    </div>
</div>

<?php
// Add specific JavaScript for this page
$additionalJS = ["js/students.js"];

// Include footer
require_once '../shared/includes/footer.php';
?>

<!-- Add page-specific JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Tab switching functionality
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs and content
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });    // Search functionality for student list
    document.getElementById('student-search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('students-table');
        const rows = table.getElementsByTagName('tr');
        
        // Start from row 1 to skip header row
        for (let i = 1; i < rows.length; i++) {
            const studentId = rows[i].cells[0].textContent.toLowerCase();
            const studentName = rows[i].cells[1].textContent.toLowerCase();
            const course = rows[i].cells[2].textContent.toLowerCase();
            const email = rows[i].cells[3].textContent.toLowerCase();
            
            const shouldShow = studentId.includes(searchTerm) || 
                             studentName.includes(searchTerm) || 
                             course.includes(searchTerm) || 
                             email.includes(searchTerm);
            
            rows[i].style.display = shouldShow ? '' : 'none';
        }
    });

    // Search functionality for finance table
    document.getElementById('finance-search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('finance-table');
        const rows = table.getElementsByTagName('tr');
        
        // Start from row 1 to skip header row
        for (let i = 1; i < rows.length; i++) {
            const studentId = rows[i].cells[0].textContent.toLowerCase();
            const studentName = rows[i].cells[1].textContent.toLowerCase();
            
            const shouldShow = studentId.includes(searchTerm) || studentName.includes(searchTerm);
            
            rows[i].style.display = shouldShow ? '' : 'none';
        }
    });

    // Modal functionality
    const modal = document.getElementById('student-details-modal');
    const closeBtn = document.querySelector('.close');
    
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Function to view student details
    function viewStudentDetails(studentId) {
        // AJAX call to get student details
        $.ajax({
            url: 'get_student_details.php',
            type: 'GET',
            data: { id: studentId },
            success: function(response) {
                document.getElementById('student-details-content').innerHTML = response;
                modal.style.display = "block";
            },
            error: function() {
                alert('Error fetching student details');
            }
        });
    }

    // Function to redirect to edit student page
    function editStudent(studentId) {
        window.location.href = 'edit_student.php?id=' + studentId;
    }    // Function to switch to finance tab and filter for specific student
    function viewFinance(studentId) {
        // Switch to finance tab
        document.querySelector('[data-tab="finance-info"]').click();
        
        // Wait a moment for the tab to switch, then set the search
        setTimeout(() => {
            const searchBox = document.getElementById('finance-search');
            if (searchBox) {
                searchBox.value = studentId;
                
                // Trigger the search
                const event = new Event('keyup');
                searchBox.dispatchEvent(event);
                
                // Focus the search box
                searchBox.focus();
            }
        }, 100);
    }
</script>