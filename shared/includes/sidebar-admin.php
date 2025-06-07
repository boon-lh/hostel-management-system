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
        </a>
        <a href="announcements.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        
        <div class="menu-category">Finance</div>
        <a href="bill_details.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'bill_details.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i> Billing
        </a>
        <a href="payment_receipt.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'payment_receipt.php' ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i> Payments
        </a>
        
        <div class="menu-category">Admin</div>
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
    </div>
</div>