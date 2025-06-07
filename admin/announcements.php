<?php
session_start();
require_once '../shared/includes/db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$message = '';
$messageType = '';
$editId = '';
$editTitle = '';
$editContent = '';

// Delete announcement
if (isset($_POST['delete'])) {
    $announcement_id = $_POST['announcement_id'];
    $deleteStmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $deleteStmt->bind_param("i", $announcement_id);
    
    if ($deleteStmt->execute()) {
        $message = "Announcement deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting announcement: " . $conn->error;
        $messageType = "danger";
    }
    $deleteStmt->close();
}

// Status change functionality removed as requested

// Get announcement for editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $editStmt->bind_param("i", $edit_id);
    $editStmt->execute();
    $result = $editStmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $editId = $row['id'];
        $editTitle = $row['title'];
        $editContent = $row['content'];
    }
    $editStmt->close();
}

// Add or Update announcement
if (isset($_POST['submit'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $admin_id = $_SESSION['user_id'];
      if (isset($_POST['announcement_id']) && !empty($_POST['announcement_id'])) {
        // Update existing announcement
        $announcement_id = $_POST['announcement_id'];
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, admin_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $title, $content, $admin_id, $announcement_id);
        
        if ($stmt->execute()) {
            $message = "Announcement updated successfully!";
            $messageType = "success";
            // Reset edit variables
            $editId = '';
            $editTitle = '';
            $editContent = '';
        } else {
            $message = "Error updating announcement: " . $conn->error;
            $messageType = "danger";
        }
    } else {
        // Add new announcement
        $stmt = $conn->prepare("INSERT INTO announcements (admin_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $admin_id, $title, $content);
        
        if ($stmt->execute()) {
            $message = "Announcement added successfully!";
            $messageType = "success";
        } else {
            $message = "Error adding announcement: " . $conn->error;
            $messageType = "danger";
        }
    }
    $stmt->close();
}

// Fetch all announcements
$query = "SELECT a.*, adm.name as admin_name 
          FROM announcements a 
          LEFT JOIN admins adm ON a.admin_id = adm.id 
          ORDER BY a.created_at DESC";
$result = $conn->query($query);
$announcements = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Set page title and additional CSS files
$pageTitle = "Announcements - MMU Hostel Management System";
$pageHeading = "Announcements";
$additionalCSS = ["css/dashboard.css"];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once '../shared/includes/sidebar-admin.php';
?>

<style>
    .actions .btn-group {
        display: flex;
    }
    .actions form {
        margin: 0 2px;
    }
    .table td.actions {
        text-align: center;
    }
    @media (max-width: 768px) {
        .table td.actions .btn {
            padding: 0.25rem 0.4rem;
        }
    }
</style>

<!-- Main Content -->
<div class="main-content">
    <?php 
    // Include admin content header
    require_once '../shared/includes/admin-content-header.php'; 
    ?>

    <!-- Display Alert Messages -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Announcement Form Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-area">
                        <div class="card-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h2 class="card-title"><?php echo $editId ? "Edit Announcement" : "Add New Announcement"; ?></h2>
                    </div>
                </div>
                <div class="card-content">
                    <form action="announcements.php" method="POST">
                        <?php if ($editId): ?>
                            <input type="hidden" name="announcement_id" value="<?php echo $editId; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="title">Title:</label>
                            <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($editTitle); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Content:</label>
                            <textarea name="content" id="content" rows="6" class="form-control" required><?php echo htmlspecialchars($editContent); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <?php echo $editId ? "Update" : "Publish"; ?> Announcement
                            </button>
                            
                            <?php if ($editId): ?>
                                <a href="announcements.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Announcements List Card -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-area">
                        <div class="card-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <h2 class="card-title">All Announcements</h2>
                    </div>
                    <div class="card-actions">
                        <div class="search-container">
                            <input type="text" id="announcement-search" placeholder="Search announcements...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table" id="announcements-table">                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Published By</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($announcements) > 0): ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                            <td>
                                                <?php 
                                                    // Show only first 50 characters of content
                                                    echo htmlspecialchars(substr($announcement['content'], 0, 50)) . 
                                                         (strlen($announcement['content']) > 50 ? '...' : ''); 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($announcement['admin_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($announcement['created_at'])); ?></td><td class="actions" style="white-space: nowrap; width: 100px;">
                                                <div class="btn-group" role="group">
                                                    <a href="announcements.php?edit=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <form action="announcements.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No announcements found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../shared/includes/admin-footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    // Simple search functionality for announcements
    $(document).ready(function() {
        $("#announcement-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#announcements-table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
</body>
</html>
