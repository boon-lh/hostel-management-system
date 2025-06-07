<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';

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
$pageTitle = "Finance Management - MMU Hostel Management";
$additionalCSS = ["css/dashboard.css", "css/finance-management.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<div class="main-content">    <?php 
    $pageHeading = "Finance Management";
    require_once 'admin-content-header.php'; 
    ?>    <div class="content-wrapper">
        <nav class="page-navigation">
            <a href="students.php" class="nav-tab">
                <i class="fas fa-users"></i>
                Student Management
            </a>
            <a href="finance.php" class="nav-tab active">
                <i class="fas fa-file-invoice-dollar"></i>
                Finance Management
            </a>
        </nav>

        <div class="finance-overview">
            <div class="list-header">
                <div class="header-title">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h2>Financial Records</h2>
                </div>
                <div class="header-actions">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="finance-search" placeholder="Search by Student ID, Name, Semester...">
                    </div>
                    <button class="btn-export" title="Export to CSV">
                        <i class="fas fa-file-export"></i>
                        <span>Export List</span>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
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
                                    </td>                                    
                                    <td class="action-buttons">                                        
                                        <button 
                                            onclick="viewBillDetails(<?php echo $finance['student_id']; ?>)"
                                            class="action-btn" 
                                            data-type="view"
                                            title="View Bill Details">
                                            <i class="fas fa-file-invoice"></i>
                                        </button>
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
            </div>        </div>
    </div>

<?php
// Add specific JavaScript for this page
$additionalJS = ["js/finance.js"];

// Include footer
require_once '../shared/includes/footer.php';
?>

<script>    // Search functionality for finance table
    document.getElementById('finance-search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('finance-table');
        const rows = table.getElementsByTagName('tr');
        
        // Start from row 1 to skip header row
        for (let i = 1; i < rows.length; i++) {
            const studentId = rows[i].cells[0].textContent.toLowerCase();
            const studentName = rows[i].cells[1].textContent.toLowerCase();
            const semester = rows[i].cells[2].textContent.toLowerCase();
            const academicYear = rows[i].cells[3].textContent.toLowerCase();
            const status = rows[i].cells[8].textContent.toLowerCase();
            
            const shouldShow = studentId.includes(searchTerm) || 
                             studentName.includes(searchTerm) || 
                             semester.includes(searchTerm) ||
                             academicYear.includes(searchTerm) ||
                             status.includes(searchTerm);
            
            rows[i].style.display = shouldShow ? '' : 'none';
        }
    });
</script>
