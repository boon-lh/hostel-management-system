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

// Set page title and additional CSS files
$pageTitle = "Student Management - MMU Hostel Management";
$additionalCSS = ["css/dashboard.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';
?>

<div class="main-content">
    <?php 
    $pageHeading = "Student Management";
    require_once '../shared/includes/admin-content-header.php'; 
    ?>

    <div class="card">
        <div class="card-header">
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
            <div class="table-responsive">
                <table class="data-table" id="students-table">
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
                                    <td><?php echo htmlspecialchars($student['citizenship']); ?></td>
                                    <td class="action-buttons">
                                        <a href="javascript:void(0)" onclick="viewStudentDetails(<?php echo $student['id']; ?>)" title="View Student Details" class="action-btn">
                                            <i class="fas fa-eye"></i>
                                            <span class="action-text">Details</span>
                                        </a>
                                        <a href="javascript:void(0)" onclick="editStudent(<?php echo $student['id']; ?>)" title="Edit Student Information" class="action-btn">
                                            <i class="fas fa-edit"></i>
                                            <span class="action-text">Edit</span>
                                        </a>
                                        <a href="finance.php?student_id=<?php echo $student['id']; ?>" title="View Financial Records" class="action-btn">
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

<script>
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
    }

    // Search functionality for student list
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
</script>