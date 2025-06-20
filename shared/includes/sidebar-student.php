<?php
// Sidebar for student dashboard
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>MMU Hostel</h2>
        <p>Student Portal</p>
    </div>    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>        <a href="hostel_registration.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'hostel_registration.php' ? 'active' : ''; ?>">
            <i class="fas fa-hotel"></i> Hostel Registration
        </a>
        <a href="my_registrations.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'my_registrations.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> My Registrations
        </a><a href="billing.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'billing.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice"></i> Billing
        </a>        <a href="complaints.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-alt"></i> Complaints & Services
        </a>
        <a href="announcements.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
        <div class="sidebar-divider"></div>
        <a href="../logout.php" class="menu-item logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>