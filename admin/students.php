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
$additionalCSS = ["css/dashboard.css", "css/student-management.css"];

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

    <div class="content-wrapper">
        <nav class="page-navigation">
            <a href="students.php" class="nav-tab active">
                <i class="fas fa-users"></i>
                Student Management
            </a>
            <a href="finance.php" class="nav-tab">
                <i class="fas fa-file-invoice-dollar"></i>
                Finance Management
            </a>
        </nav>

        <div class="students-list">
            <div class="list-header">
                <div class="header-title">
                    <i class="fas fa-users"></i>
                    <h2>All Student List</h2>
                </div>
                <div class="header-actions">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="student-search" placeholder="Search by ID, name, course or email...">
                    </div>
                    <button class="btn-export" title="Export to CSV">
                        <i class="fas fa-file-export"></i>
                        <span>Export List</span>
                    </button>
                </div>
            </div>

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
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>                                    <td><?php echo htmlspecialchars($student['citizenship']); ?></td>                                    <td class="action-buttons">
                                        <button type="button" 
                                            onclick="editStudent(<?php echo $student['id']; ?>)" 
                                            class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
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

<?php
// Add specific JavaScript for this page
$additionalJS = ["js/students.js"];

// Include footer
require_once '../shared/includes/footer.php';
?>

<script>    // Function to redirect to edit student page
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