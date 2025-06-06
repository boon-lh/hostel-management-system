<?php
session_start();
// Include database connection
include_once '../shared/includes/db_connection.php';
// Include header
include_once '../shared/includes/header.php';
// Include student sidebar
include_once '../shared/includes/sidebar-student.php';

// Check if student is logged in, otherwise redirect to login page
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

// Fetch announcements from database
$announcements = [];

// Check if the database table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'announcements'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    // Fetch all announcements ordered by date (newest first)
    $sql = "SELECT a.*, ad.name as admin_name 
            FROM announcements a 
            LEFT JOIN admins ad ON a.admin_id = ad.id 
            ORDER BY a.created_at DESC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
} else {
    // If table doesn't exist, use placeholder announcements
    $announcements = [
        [
            'title' => 'Welcome to the New Semester',
            'content' => 'We welcome all students to the new semester. The hostel facilities are now fully operational.',
            'admin_name' => 'Admin',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'title' => 'Maintenance Notice',
            'content' => 'The water supply will be interrupted on Saturday from 10 AM to 2 PM due to scheduled maintenance.',
            'admin_name' => 'Admin',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
}
?>

<link rel="stylesheet" href="css/announcements.css">

<div class="main-content">
    <div class="container">
        <h2>Announcements & Notices</h2>
        <p>Stay updated with important notices and announcements from the hostel administration</p>

        <div class="announcement-list">
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <span class="announcement-date">
                                <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                            </span>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                        <div class="announcement-footer">
                            <span class="announcement-author">Posted by: <?php echo htmlspecialchars($announcement['admin_name']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-announcements">
                    <p>There are no announcements at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../shared/includes/footer.php';
?>
