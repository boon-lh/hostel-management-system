<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

// Get all students with sorting
$sortColumn = 'id'; // Default sort by id
$sortDirection = 'ASC'; // Default direction

// Check if sort parameters exist
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    // Whitelist of allowed columns for sorting
    $allowedColumns = ['id', 'name', 'course', 'email', 'gender', 'citizenship'];
    if (in_array($_GET['sort'], $allowedColumns)) {
        $sortColumn = $_GET['sort'];
    }
}

if (isset($_GET['direction']) && in_array(strtoupper($_GET['direction']), ['ASC', 'DESC'])) {
    $sortDirection = strtoupper($_GET['direction']);
}

$students_query = "SELECT * FROM students ORDER BY {$sortColumn} {$sortDirection}";
$students_result = $conn->query($students_query);
$students = [];
if ($students_result && $students_result->num_rows > 0) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Set page title and additional CSS/JS files
$pageTitle = "Student Management - MMU Hostel Management";
// CSS paths relative to admin directory
$additionalCSS = ["css/dashboard.css", "css/student-management.css"];
// Add necessary JavaScript files in correct order
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
    $pageHeading = "Student Management";
    require_once 'admin-content-header.php'; 
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
            <div class="list-header">                <div class="header-title">
                    <i class="fas fa-users"></i>
                    <h2>All Student List</h2>
                    <?php
                    $sortName = '';
                    switch($sortColumn) {
                        case 'id': $sortName = 'Student ID'; break;
                        case 'name': $sortName = 'Name'; break;
                        case 'course': $sortName = 'Course'; break;
                        case 'gender': $sortName = 'Gender'; break;
                        case 'citizenship': $sortName = 'Citizenship'; break;
                        default: $sortName = 'Student ID';
                    }
                    $sortDirText = ($sortDirection == 'ASC') ? 'Ascending' : 'Descending';
                    ?>
                    <span class="sort-indicator">(Sorted by: <?= $sortName ?> - <?= $sortDirText ?>)</span>
                </div><div class="header-actions">                    
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="student-search" placeholder="Search by ID, name, course or email...">                    </div>
                    <div class="sort-buttons">
                        <label for="sort-select">Sort by:</label>
                        <select id="sort-select" class="sort-select">
                            <option value="id">Student ID</option>
                            <option value="name">Name</option>
                            <option value="course">Course</option>
                            <option value="gender">Gender</option>
                            <option value="citizenship">Citizenship</option>
                        </select>
                        <button id="sort-direction" class="btn-sort" title="Toggle Sort Direction">
                            <i class="fas fa-sort-up"></i>
                        </button>
                    </div>
                    <button class="btn-export">
                        <i class="fas fa-file-export"></i>
                        <span>Export List</span>
                    </button>
                </div>            
            </div>            
            <div class="table-responsive" role="region" aria-label="Students List">                <table class="data-table" id="students-table" aria-label="Students Information Table">
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
                    <tbody>                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr data-student-id="<?php echo htmlspecialchars($student['id']); ?>">
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['contact_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>                                    
                                    <td><?php echo htmlspecialchars($student['citizenship']); ?></td>
                                    <td class="action-buttons">                                            <a href="student_details.php?id=<?php echo $student['id']; ?>" 
                                            class="action-btn"                                            
                                            data-type="view"
                                            title="View Student Details">
                                            <i class="fas fa-eye"></i>
                                        </a>                                        <button type="button" 
                                            onclick="editStudent(<?php echo (int)$student['id']; ?>)" 
                                            class="action-btn"                                            
                                            data-type="edit"
                                            data-student-id="<?php echo (int)$student['id']; ?>"
                                            title="Edit Student Information">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                            onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo addslashes(htmlspecialchars($student['name'])); ?>')" 
                                            class="action-btn"                                            
                                            data-type="delete"
                                            title="Delete Student">
                                            <i class="fas fa-trash-alt"></i>
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
// Include footer
require_once '../shared/includes/footer.php';
?>