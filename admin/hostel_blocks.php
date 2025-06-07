<?php
// filepath: c:\xampp\htdocs\hostel-management-system\admin\hostel_blocks.php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../shared/includes/db_connection.php";

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Hostel Blocks";
$additionalCSS = ["css/dashboard.css", "css/hostel_blocks.css"];

// Define the specific four hostel blocks as requested
$blocks = [
    [
        'id' => 1,
        'block_name' => 'Block A',
        'gender_restriction' => 'Male',
        'nationality_restriction' => 'Local',
        'description' => 'Hostel block for local male students with standard facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'block_name' => 'Block B',
        'gender_restriction' => 'Female',
        'nationality_restriction' => 'Local',
        'description' => 'Hostel block for local female students with standard facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 3,
        'block_name' => 'Block C',
        'gender_restriction' => 'Male',
        'nationality_restriction' => 'International',
        'description' => 'Hostel block for international male students with cultural integration facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 4,
        'block_name' => 'Block D',
        'gender_restriction' => 'Female',
        'nationality_restriction' => 'International',
        'description' => 'Hostel block for international female students with cultural integration facilities.',
        'created_at' => date('Y-m-d H:i:s')
    ]
];

// Include header
require_once '../shared/includes/header.php';

// Include admin sidebar
require_once 'sidebar-admin.php';
?>

<!-- Main Content -->
<div class="main-content">    <?php 
    $pageHeading = "Hostel Block Management";
    require_once 'admin-content-header.php'; 
    ?>
    
    <!-- Blocks Overview -->
    <div class="card">
        <div class="card-header">
            <div class="card-title-area">
                <div class="card-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h2 class="card-title">Hostel Blocks</h2>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($blocks)): ?>
                <div class="no-data-container">
                    <div class="no-data-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3>No Hostel Blocks Found</h3>
                    <p>There are no hostel blocks in the system yet.</p>
                </div>
            <?php else: ?>
                <div class="hostel-blocks-grid">
                    <?php foreach ($blocks as $block): ?>
                        <div class="block-card">
                            <div class="block-header">
                                <h3><?= htmlspecialchars($block['block_name']) ?></h3>
                            </div>
                            <div class="block-details">
                                <p class="block-type">
                                    <?php 
                                    $genderIcon = 'fas fa-question-circle';
                                    $genderText = 'Unknown';
                                    $genderClass = 'status-neutral';
                                    
                                    switch ($block['gender_restriction']) {
                                        case 'Male':
                                            $genderIcon = 'fas fa-male';
                                            $genderText = 'Males Only';
                                            $genderClass = 'status-male';
                                            break;
                                        case 'Female':
                                            $genderIcon = 'fas fa-female';
                                            $genderText = 'Females Only';
                                            $genderClass = 'status-female';
                                            break;
                                        case 'Mixed':
                                            $genderIcon = 'fas fa-venus-mars';
                                            $genderText = 'Mixed Gender';
                                            $genderClass = 'status-mixed';
                                            break;
                                        case 'None':
                                            $genderIcon = 'fas fa-users';
                                            $genderText = 'No Restriction';
                                            $genderClass = 'status-none';
                                            break;
                                    }
                                    ?>
                                    <span class="status <?= $genderClass ?>">
                                        <i class="<?= $genderIcon ?>"></i> <?= $genderText ?>
                                    </span>
                                </p>
                                
                                <p class="block-nationality">
                                    <?php 
                                    $natIcon = 'fas fa-globe';
                                    $natText = 'All Students';
                                    $natClass = 'status-neutral';
                                    
                                    switch ($block['nationality_restriction']) {
                                        case 'Local':
                                            $natIcon = 'fas fa-flag';
                                            $natText = 'Local Students';
                                            $natClass = 'status-local';
                                            break;
                                        case 'International':
                                            $natIcon = 'fas fa-globe-americas';
                                            $natText = 'International Students';
                                            $natClass = 'status-international';
                                            break;
                                        case 'Mixed':
                                            $natIcon = 'fas fa-globe';
                                            $natText = 'Mixed Students';
                                            $natClass = 'status-mixed';
                                            break;
                                        case 'None':
                                            $natIcon = 'fas fa-users';
                                            $natText = 'No Restriction';
                                            $natClass = 'status-none';
                                            break;
                                    }
                                    ?>
                                    <span class="status <?= $natClass ?>">
                                        <i class="<?= $natIcon ?>"></i> <?= $natText ?>
                                    </span>
                                </p>
                                
                                <?php if (!empty($block['description'])): ?>
                                <div class="block-description">
                                    <p><?= nl2br(htmlspecialchars($block['description'])) ?></p>
                                </div>
                                <?php endif; ?>
                                  <div class="block-actions">
                                    <a href="block_rooms.php?block_id=<?= $block['id'] ?>" class="action-btn view-btn" title="View Rooms" data-id="<?= $block['id'] ?>">
                                        <i class="fas fa-door-open"></i> <span class="action-text">View Rooms</span>
                                    </a>
                                    <a href="#" class="action-btn edit-btn" title="Edit Block" data-id="<?= $block['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // View rooms button functionality - redirect to block_rooms.php
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const blockId = this.getAttribute('data-id');
                window.location.href = `block_rooms.php?block_id=${blockId}`;
            });
        });
        
        // Edit block button functionality (placeholder for now)
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const blockId = this.getAttribute('data-id');
                const blockName = this.closest('.block-card').querySelector('.block-header h3').textContent;
                alert(`Edit ${blockName} functionality will be implemented here.`);
            });
        });
    });
</script>

<?php require_once '../shared/includes/footer.php'; ?>
