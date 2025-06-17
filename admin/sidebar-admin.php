<?php
// Sidebar for admin dashboard
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>MMU Hostel</h2>
        <p>Admin Portal</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
          <div class="menu-category">Student Management</div>
        <a href="students.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-graduate"></i> Students
        </a>        <a href="visitors.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'visitors.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-friends"></i> Visitors
        </a>        <a href="announcements.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="complaints.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-alt"></i> Complaints
        </a>
          <div class="menu-category">Hostel Management</div>
        <a href="hostel_blocks.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'hostel_blocks.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i> Hostel Blocks
        </a>        <?php
        // Get count of pending room registrations
        $pending_count = 0;
        if (isset($conn)) {
            $pending_query = "SELECT COUNT(*) as count FROM hostel_registrations WHERE status = 'Pending'";
            $pending_result = $conn->query($pending_query);
            if ($pending_result && $pending_result->num_rows > 0) {
                $pending_count = $pending_result->fetch_assoc()['count'];
            }
        }
        ?>
        <a href="room_registrations.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'room_registrations.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> Room Registrations
            <?php if ($pending_count > 0): ?>
                <span class="badge badge-danger"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        
        <div class="menu-category">Admin</div>
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
    </div>
</div>