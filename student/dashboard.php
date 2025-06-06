<?php
session_start();
require_once '../shared/includes/db_connection.php';
require_once '../shared/includes/header.php'; 
require_once '../shared/includes/sidebar-student.php';

// Set page title
$pageTitle = "Student Dashboard";
// $additionalCSS = ['css/dashboard.css',]; // Removed this line

?>
<style>
/* Student Dashboard CSS - Modern UI Design for Hostel Management System */

/* Main Layout */
.main-content {
    margin-left: 250px;
    padding: 20px 30px;
    min-height: calc(100vh - 60px);
    transition: all 0.3s ease;
    background: #f5f7fa;
}

/* Preloader */
.preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.98);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.5s;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(110, 142, 251, 0.3);
    border-radius: 50%;
    border-top-color: #6e8efb;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Dashboard Header */
.dashboard-header {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e6ed;
}

.dashboard-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.date-display {
    color: #7f8c8d;
    font-size: 14px;
}

/* Dashboard Stats */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    opacity: 0;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    border-radius: 10px;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 22px;
    color: white;
}

.stat-content h3 {
    font-size: 15px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.stat-content p {
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

/* Dashboard Cards Container */
.dashboard-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

/* Common Card Styles */
.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    opacity: 0;
}

.dashboard-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    background: #f8f9fc;
    border-bottom: 1px solid #e0e6ed;
}

.card-header h3 {
    color: #2c3e50;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.card-header h3 i {
    margin-right: 10px;
    color: #6e8efb;
}

.btn-view-all {
    font-size: 13px;
    color: #6e8efb;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-view-all:hover {
    color: #a777e3;
    text-decoration: underline;
}

.card-content {
    padding: 20px;
    flex: 1;
}

/* Announcements Card */
.announcements-list {
    list-style: none;
}

.announcements-list li {
    display: flex;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e6ed;
}

.announcements-list li:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.announcement-date {
    background: #f8f9fc;
    padding: 8px;
    border-radius: 6px;
    min-width: 60px;
    text-align: center;
    color: #7f8c8d;
    font-size: 13px;
    font-weight: 500;
    height: fit-content;
    margin-right: 15px;
}

.announcement-content h4 {
    font-size: 15px;
    margin-bottom: 5px;
    color: #2c3e50;
}

.announcement-content p {
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.5;
}

/* Billing Card */
.billing-summary {
    margin-bottom: 20px;
}

.billing-amount, .billing-due {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
}

.billing-amount .label, .billing-due .label {
    color: #7f8c8d;
    font-size: 14px;
}

.billing-amount .value, .billing-due .value {
    font-size: 15px;
    font-weight: 600;
    color: #2c3e50;
}

.billing-amount .value {
    font-size: 18px;
    color: #2ecc71;
}

.overdue {
    color: #e74c3c !important;
}

.payment-actions {
    display: flex;
    gap: 10px;
}

/* Complaint Card */
.complaint-summary {
    margin-bottom: 20px;
}

.complaint-summary p {
    margin-bottom: 10px;
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.5;
}

.complaint-summary p strong {
    color: #2c3e50;
}

/* Button Styles */
.btn {
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5d7df9, #9665e0);
    box-shadow: 0 4px 10px rgba(110, 142, 251, 0.3);
    transform: translateY(-2px);
}

.btn-secondary {
    background: #f8f9fc;
    color: #6e8efb;
    border: 1px solid #e0e6ed;
}

.btn-secondary:hover {
    background: #e8eaf6;
    color: #5d7df9;
    border-color: #d0d7e9;
}

/* No Data Message */
.no-data {
    color: #95a5a6;
    text-align: center;
    padding: 20px;
    font-size: 14px;
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.5s ease forwards;
}

/* Responsive Design */
@media (max-width: 991px) {
    .main-content {
        margin-left: 0;
        padding: 15px 20px;
    }
    
    .dashboard-stats, .dashboard-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .dashboard-header h1 {
        font-size: 24px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
    }
    
    .payment-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>
<?php

$student_id = $_SESSION['student_id'] ?? 1; // Default to 1 if not set

// Fetch announcements
// Use try-catch to handle potential errors with table not existing
try {
    $announcement_query = "SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3";
    $announcement_result = mysqli_query($conn, $announcement_query);
} catch (mysqli_sql_exception $e) {
    // If table doesn't exist, set result to null
    $announcement_result = null;
}

// Fetch billing information
// Use try-catch to handle potential errors with table not existing
$billing_info = null;
try {
    $billing_query = "SELECT * FROM billing WHERE student_id = ? ORDER BY due_date ASC LIMIT 1";
    $billing_stmt = mysqli_prepare($conn, $billing_query);
    mysqli_stmt_bind_param($billing_stmt, "i", $student_id);
    mysqli_stmt_execute($billing_stmt);
    $billing_result = mysqli_stmt_get_result($billing_stmt);
    $billing_info = mysqli_fetch_assoc($billing_result);
} catch (mysqli_sql_exception $e) {
    // If table doesn't exist, leave billing_info as null
}

// Fetch active complaints count
// Use try-catch to handle potential errors with table not existing
$active_complaints = 0;
try {
    $complaints_query = "SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status != 'closed'";
    $complaints_stmt = mysqli_prepare($conn, $complaints_query);
    mysqli_stmt_bind_param($complaints_stmt, "i", $student_id);
    mysqli_stmt_execute($complaints_stmt);
    $complaints_result = mysqli_stmt_get_result($complaints_stmt);
    $active_complaints = mysqli_fetch_assoc($complaints_result)['count'];
} catch (mysqli_sql_exception $e) {
    // If table doesn't exist, leave active_complaints as 0
}
?>


<!-- Preloader -->
<div class="preloader">
    <div class="spinner"></div>
</div>

<div class="main-content">
    <div class="dashboard-header">
        <?php
        $hour = date('H');
        $greeting = '';
        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 18) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }
        ?>
        <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></h1>
        <p class="date-display"><?php echo date('l, F j, Y'); ?></p>
    </div>
      <div class="dashboard-stats">
        <div class="stat-card stagger-item">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-content">
                <h3>Past</h3>
                <p>5 Active</p>
            </div>        </div>
        <div class="stat-card stagger-item">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-content">
                <h3>Upcoming</h3>
                <p>3 Active</p>
            </div>        </div>
        <div class="stat-card stagger-item">
            <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="stat-content">
                <h3>CGPA</h3>
                <p>3.95</p>
            </div>        </div>
        <div class="stat-card stagger-item">
            <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="stat-content">
                <h3>Balance</h3>
                <p><?php echo isset($billing_info['balance']) ? '$'.number_format($billing_info['balance'], 2) : '$0.00'; ?></p>
            </div>
        </div>
    </div>    <div class="dashboard-container">
        <!-- Announcements Section -->        <div class="dashboard-card announcements-card stagger-item">
            <div class="card-header">
                <h3><i class="fas fa-bullhorn"></i> Announcements</h3>
                <a href="announcements.php" class="btn-view-all">View All</a>
            </div>
            <div class="card-content">
                <?php if (isset($announcement_result) && $announcement_result && mysqli_num_rows($announcement_result) > 0): ?>
                    <ul class="announcements-list">
                        <?php while ($announcement = mysqli_fetch_assoc($announcement_result)): ?>
                            <li>
                                <div class="announcement-date"><?php echo date('M d', strtotime($announcement['created_at'])); ?></div>
                                <div class="announcement-content">
                                    <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data">No new announcements at the moment.</p>
                <?php endif; ?>
            </div>        </div>        <!-- Billing Information Section -->
        <div class="dashboard-card billing-card stagger-item">
            <div class="card-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Billing Information</h3>
                <a href="billing.php" class="btn-view-all">View Details</a>
            </div>
            <div class="card-content">
                <?php if (isset($billing_info) && $billing_info): ?>
                    <div class="billing-summary">
                        <div class="billing-amount">
                            <span class="label">Current Balance:</span>
                            <span class="value"><?php echo '$'.number_format($billing_info['balance'], 2); ?></span>
                        </div>
                        <div class="billing-due">
                            <span class="label">Next Due Date:</span>
                            <span class="value <?php echo strtotime($billing_info['due_date']) < time() ? 'overdue' : ''; ?>">
                                <?php echo date('F j, Y', strtotime($billing_info['due_date'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="payment-actions">
                        <a href="make_payment.php" class="btn btn-primary">Make Payment</a>
                        <a href="payment_history.php" class="btn btn-secondary">View History</a>
                    </div>
                <?php else: ?>
                    <p class="no-data">No billing information available.</p>
                    <div class="payment-actions">
                        <a href="billing.php" class="btn btn-primary">View Billing Details</a>
                    </div>
                <?php endif; ?>
            </div>        </div>        <!-- Complaint Card Section -->
        <div class="dashboard-card complaint-card stagger-item">
            <div class="card-header">
                <h3><i class="fas fa-exclamation-circle"></i> Complaints</h3>
                <a href="complaints.php" class="btn-view-all">View All</a>
            </div>
            <div class="card-content">
                <div class="complaint-summary">
                    <p>You have <strong><?php echo $active_complaints; ?></strong> active complaint(s).</p>
                    <p>Need assistance? We're here to help!</p>
                </div>
                <div class="complaint-actions">
                    <a href="complaints_new.php" class="btn btn-primary">File a New Complaint</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../shared/includes/footer.php'; ?>

<script>
// Preloader
document.addEventListener('DOMContentLoaded', function() {
    // Hide preloader after page loads
    setTimeout(function() {
        const preloader = document.querySelector('.preloader');
        preloader.style.opacity = '0';
        setTimeout(function() {
            preloader.style.display = 'none';
            
            // Animate staggered items
            const staggerItems = document.querySelectorAll('.stagger-item');
            staggerItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.classList.add('fade-in-up');
                }, 100 * (index + 1));
            });
        }, 500);
    }, 800);
});
</script>
</body>
</html>