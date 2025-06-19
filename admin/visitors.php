<?php
session_start();
require_once '../shared/includes/db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch all visitor records from database
$query = "SELECT v.*, r.room_number as room_number_from_rooms
          FROM visitors v
          LEFT JOIN rooms r ON v.room_number = r.room_number
          ORDER BY v.visit_date DESC, v.time_in DESC";

$result = $conn->query($query);
$visitors = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $visitors[] = $row;
    }
}

// Set page title and additional CSS files
$pageTitle = "Visitor Records - MMU Hostel Management System";
$pageHeading = "Visitor Records";
$additionalCSS = ["css/dashboard.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<!-- Main Content -->
<div class="main-content">
    <?php 
    // Include admin content header
    require_once 'admin-content-header.php'; 
    ?>

    <!-- Visitors List Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title-area">
                <div class="card-icon">
                    <i class="fas fa-user-friends"></i>
                </div>
                <h2 class="card-title">Visitor Records</h2>
            </div>
            <div class="card-actions">
                <div class="search-container">
                    <input type="text" id="visitor-search" placeholder="Search visitor...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
        <div class="card-content">
            <div class="table-responsive">                <table class="data-table" id="visitors-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="name">Visitor Name <i class="fas fa-sort"></i></th>
                            <th>Phone Number</th>
                            <th class="sortable" data-sort="gender">Gender <i class="fas fa-sort"></i></th>
                            <th>IC Number</th>
                            <th class="sortable" data-sort="date">Visit Date <i class="fas fa-sort"></i></th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Room Number</th>
                            <th>Car Plate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($visitors) > 0): ?>
                            <?php foreach ($visitors as $visitor): ?>                                <tr>
                                    <td data-value="<?php echo htmlspecialchars($visitor['name']); ?>"><?php echo htmlspecialchars($visitor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['contact_no']); ?></td>
                                    <td data-value="<?php echo htmlspecialchars($visitor['gender']); ?>"><?php echo htmlspecialchars($visitor['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['ic_number']); ?></td>
                                    <td data-value="<?php echo $visitor['visit_date']; ?>"><?php echo date('d M Y', strtotime($visitor['visit_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($visitor['time_in'])); ?></td>
                                    <td>
                                        <?php 
                                            echo $visitor['time_out'] ? date('h:i A', strtotime($visitor['time_out'])) : 'Not checked out';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['car_plate'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No visitor records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<style>
    /* Style for sortable columns */
    th.sortable {
        cursor: pointer;
        position: relative;
    }
    th.sortable i.fas {
        margin-left: 5px;
        font-size: 0.8em;
        color: #aaa;
    }
    th.sortable.asc i.fas:before {
        content: "\f0de"; /* fa-sort-up */
        color: #333;
    }
    th.sortable.desc i.fas:before {
        content: "\f0dd"; /* fa-sort-down */
        color: #333;
    }
</style>

<script>
    // Search and sorting functionality for visitors table
    $(document).ready(function() {
        // Search functionality
        $("#visitor-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Sorting functionality
        $("th.sortable").on("click", function() {
            var table = $(this).parents("table").eq(0);
            var rows = table.find("tr:gt(0)").toArray().sort(comparer($(this).index()));
            var sortDir = this.asc = !this.asc ? true : !this.asc;
            
            // Update sort icons
            $("th.sortable").removeClass("asc desc");
            $(this).addClass(sortDir ? "asc" : "desc");
            
            // Sort rows
            if (!sortDir) {
                rows = rows.reverse();
            }
            
            // Append sorted rows to table
            for (var i = 0; i < rows.length; i++) {
                table.append(rows[i]);
            }
        });
        
        // Comparison function for sorting
        function comparer(index) {
            return function(a, b) {
                var valA = getCellValue(a, index);
                var valB = getCellValue(b, index);
                
                // Handle date comparison
                if ($("th").eq(index).data("sort") === "date") {
                    valA = new Date(valA);
                    valB = new Date(valB);
                }
                
                return $.isNumeric(valA) && $.isNumeric(valB) ? 
                    valA - valB : valA.localeCompare(valB);
            };
        }
        
        // Get cell value for sorting
        function getCellValue(row, index) {
            return $(row).children('td').eq(index).text().trim();
        }
    });
</script>
</body>
</html>
