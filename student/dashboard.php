<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../shared/includes/db_connection.php';

// Set page title and additional CSS
$pageTitle = "Student Dashboard";
// Use only the main dashboard CSS
$additionalCSS = ["css/dashboard.css"];

// Fetch announcements from database
$announcements = [];

// Check if the database table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'announcements'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    // Fetch the 3 most recent announcements
    $sql = "SELECT a.*, ad.name as admin_name 
            FROM announcements a 
            LEFT JOIN admins ad ON a.admin_id = ad.id 
            ORDER BY a.created_at DESC
            LIMIT 3";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
}

// Include header and sidebar
require_once '../shared/includes/header.php'; 
require_once '../shared/includes/sidebar-student.php';
?>

<!-- Main content area -->
<div style="margin-left: 250px; padding: 20px; background: #f8f9fc; min-height: 100vh; box-sizing: border-box;">
    <!-- Page header -->    <div style="margin-bottom: 24px;">
        <h1 style="margin: 0 0 8px 0; font-weight: 700; font-size: 28px; color: #2c3e50;">Dashboard</h1>
        <p style="font-size: 16px; color: #858796; margin: 0;">Welcome, <?php echo $_SESSION['student_name'] ?? 'Student'; ?>!</p>
    </div>
    
    <!-- Dashboard grid layout -->
    <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 24px;">
        <!-- Announcements Card -->
        <div style="flex: 1; min-width: 300px;">
            <div style="background: white; border-radius: 8px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); overflow: hidden; margin-bottom: 24px; border: 1px solid #e3e6f0; transition: transform 0.2s, box-shadow 0.2s;">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e3e6f0; background: #f8f9fc; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="margin: 0; font-weight: 600; font-size: 18px; color: #2c3e50; display: flex; align-items: center;">
                        <span style="margin-right: 8px; color: #4e73df; font-size: 20px;">📢</span> Announcements
                    </h3>
                </div>
                <div style="padding: 20px;">
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $announcement): ?>
                        <div style="padding: 16px 0; border-bottom: 1px solid #e3e6f0;">
                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #2c3e50;">
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </h4>
                            <p style="font-size: 12px; color: #858796; margin: 5px 0;">
                                Posted <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?> by 
                                <?php echo htmlspecialchars($announcement['admin_name'] ?? 'Admin'); ?>
                            </p>
                            <p style="font-size: 14px; line-height: 1.5; margin: 8px 0 0 0; color: #444;">
                                <?php 
                                // Show a snippet of the content
                                echo strlen($announcement['content']) > 100 ? 
                                    htmlspecialchars(substr($announcement['content'], 0, 100)) . '...' : 
                                    htmlspecialchars($announcement['content']); 
                                ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                        <a href="announcements.php" style="display: block; margin-top: 16px; text-align: right; color: #4e73df; text-decoration: none; font-size: 14px; font-weight: 600;">View all announcements →</a>
                    <?php else: ?>
                        <p>No new announcements at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- FAQ Card -->
        <div style="flex: 2; min-width: 400px;">
            <div style="background: white; border-radius: 8px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); overflow: hidden; margin-bottom: 24px; border: 1px solid #e3e6f0;">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e3e6f0; background: #f8f9fc; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="margin: 0; font-weight: 600; font-size: 18px; color: #2c3e50; display: flex; align-items: center;">
                        <span style="margin-right: 8px; color: #4e73df; font-size: 20px;">❓</span> Frequently Asked Questions
                    </h3>
                </div>
                <div style="padding: 20px;">
                    <div style="border-bottom: 1px solid #e3e6f0;">
                        <div onclick="toggle(1)" style="padding: 14px 0; cursor: pointer; font-weight: 600; font-size: 15px; color: #2c3e50; display: flex; align-items: center;">
                            <i class="fas fa-chevron-right" style="margin-right: 10px; font-size: 14px; color: #4e73df;"></i> How do I register for a room?
                        </div>
                        <div id="faq1" style="display: none; padding: 0 0 16px 24px; font-size: 14px; line-height: 1.6; color: #444;">
                            You can register for a room through the "Hostel Registration" menu option in the sidebar.
                        </div>
                    </div>
                    
                    <div style="border-bottom: 1px solid #e3e6f0;">
                        <div onclick="toggle(2)" style="padding: 14px 0; cursor: pointer; font-weight: 600; font-size: 15px; color: #2c3e50; display: flex; align-items: center;">
                            <i class="fas fa-chevron-right" style="margin-right: 10px; font-size: 14px; color: #4e73df;"></i> How do I file a complaint?
                        </div>
                        <div id="faq2" style="display: none; padding: 0 0 16px 24px; font-size: 14px; line-height: 1.6; color: #444;">
                            You can file a complaint through the "Complaints & Services" section in the sidebar.
                        </div>
                    </div>
                    
                    <div style="border-bottom: 1px solid #e3e6f0;">
                        <div onclick="toggle(3)" style="padding: 14px 0; cursor: pointer; font-weight: 600; font-size: 15px; color: #2c3e50; display: flex; align-items: center;">
                            <i class="fas fa-chevron-right" style="margin-right: 10px; font-size: 14px; color: #4e73df;"></i> How do I view my registrations?
                        </div>
                        <div id="faq3" style="display: none; padding: 0 0 16px 24px; font-size: 14px; line-height: 1.6; color: #444;">
                            You can view your room details in the "My Registrations" section.
                        </div>
                    </div>
                    
                    <div style="border-bottom: 1px solid #e3e6f0;">
                        <div onclick="toggle(4)" style="padding: 14px 0; cursor: pointer; font-weight: 600; font-size: 15px; color: #2c3e50; display: flex; align-items: center;">
                            <i class="fas fa-chevron-right" style="margin-right: 10px; font-size: 14px; color: #4e73df;"></i> How do I pay my hostel fees?
                        </div>
                        <div id="faq4" style="display: none; padding: 0 0 16px 24px; font-size: 14px; line-height: 1.6; color: #444;">
                            You can pay your fees through the online payment system or directly at the finance office.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div style="background: white; border-radius: 8px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); overflow: hidden; margin-top: 24px; border: 1px solid #e3e6f0;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e3e6f0; background: #f8f9fc; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-weight: 600; font-size: 18px; color: #2c3e50; display: flex; align-items: center;">
                <span style="margin-right: 8px; color: #4e73df; font-size: 20px;">🔗</span> Quick Links
            </h3>
        </div>
        <div style="padding: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 12px;">
                <a href="hostel_registration.php" style="display: flex; align-items: center; background: white; padding: 12px 16px; border-radius: 6px; text-decoration: none; color: #2c3e50; font-weight: 500; border: 1px solid #e3e6f0; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
                    <span style="margin-right: 8px; font-size: 18px;">🏨</span> Room Registration
                </a>
                <a href="my_registrations.php" style="display: flex; align-items: center; background: white; padding: 12px 16px; border-radius: 6px; text-decoration: none; color: #2c3e50; font-weight: 500; border: 1px solid #e3e6f0; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
                    <span style="margin-right: 8px; font-size: 18px;">📋</span> My Registrations
                </a>
                <a href="complaints.php" style="display: flex; align-items: center; background: white; padding: 12px 16px; border-radius: 6px; text-decoration: none; color: #2c3e50; font-weight: 500; border: 1px solid #e3e6f0; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
                    <span style="margin-right: 8px; font-size: 18px;">💬</span> File Complaint
                </a>
                <a href="profile.php" style="display: flex; align-items: center; background: white; padding: 12px 16px; border-radius: 6px; text-decoration: none; color: #2c3e50; font-weight: 500; border: 1px solid #e3e6f0; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
                    <span style="margin-right: 8px; font-size: 18px;">👤</span> My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggle(num) {
    var element = document.getElementById('faq' + num);
    var question = element.previousElementSibling;
    
    if (element.style.display === 'none' || element.style.display === '') {
        // Close any open FAQ answers
        for (var i = 1; i <= 4; i++) {
            var answer = document.getElementById('faq' + i);
            if (answer !== element) {
                answer.style.display = 'none';
                var q = answer.previousElementSibling;
                q.querySelector('i').style.transform = '';
            }
        }
        
        // Open this answer
        element.style.display = 'block';
        question.querySelector('i').style.transform = 'rotate(90deg)';
    } else {
        // Close this answer
        element.style.display = 'none';
        question.querySelector('i').style.transform = '';
    }
}

// Make sure all FAQs are closed on page load
document.addEventListener('DOMContentLoaded', function() {
    for (var i = 1; i <= 4; i++) {
        var answer = document.getElementById('faq' + i);
        if (answer) {
            answer.style.display = 'none';
        }
    }
});
</script>

<?php require_once '../shared/includes/footer.php'; ?>