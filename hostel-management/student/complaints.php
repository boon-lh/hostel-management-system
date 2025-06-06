<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "student") {
    header("Location: ../index.php");
    exit();
}

require_once '../shared/includes/db_connection.php';
require_once '../shared/includes/request_functions.php';  // Include the functions file

// Set page title and additional CSS files
$pageTitle = "MMU Hostel Management - Complaints & Feedback";
$additionalCSS = ["css/complaints.css"];

// Get student ID from session
$username = $_SESSION["user"];
$studentId = 0;
$errors = [];
$success = "";

// Get student ID from database
$stmt = $conn->prepare("SELECT id FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentId = $row['id'];
} else {
    $errors[] = "Student information not found.";
}

// Initialize array to hold complaints
$complaints = [];

// Process complaint submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'submit_complaint') {
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $complaint_type = $_POST['complaint_type'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        
        // Use the submitComplaint function
        $result = submitComplaint(
            $conn, 
            $studentId, 
            $subject, 
            $description, 
            $complaint_type, 
            $priority, 
            isset($_FILES['attachment']) ? $_FILES['attachment'] : null
        );
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
        
    } else if (isset($_POST['action']) && $_POST['action'] === 'add_feedback') {
        $complaint_id = $_POST['complaint_id'] ?? 0;
        $rating = $_POST['rating'] ?? 0;
        $feedback = trim($_POST['feedback'] ?? '');
        
        // Use the addComplaintFeedback function
        $result = addComplaintFeedback($conn, $complaint_id, $studentId, $rating, $feedback);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Fetch complaints for the student using the function
if ($studentId > 0) {
    $complaints = getStudentComplaints($conn, $studentId);
}

// Include header
require_once '../shared/includes/header.php';

// Include student sidebar
require_once '../shared/includes/sidebar-student.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1><i class="fas fa-comment-alt"></i> Complaints & Feedback</h1>
        
    </div>
    
    <div class="complaints-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Submit New Complaint</h3>
                    </div>
                    <div class="card-body">
                        <form action="complaints.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="submit_complaint">
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Brief subject of your complaint" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="complaint_type">Complaint Type</label>
                                <select class="form-control" id="complaint_type" name="complaint_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="hostel_facility">Hostel Facility Issue</option>
                                    <option value="roommate">Roommate Issue</option>
                                    <option value="staff">Staff Behavior</option>
                                    <option value="internet">Internet Issue</option>
                                    <option value="cleanliness">Cleanliness Issue</option>
                                    <option value="cafeteria">Cafeteria Issue</option>
                                    <option value="security">Security Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">Priority</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Please provide detailed information about your complaint" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachment">Attachment (optional)</label>
                                <input type="file" class="form-control-file" id="attachment" name="attachment">
                                <small class="form-text text-muted">You can attach a photo or document (JPG, PNG, GIF, PDF) up to 5MB.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i> Submit Complaint
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> My Complaints</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($complaints)): ?>
                            <div class="no-complaints-message">
                                <i class="fas fa-info-circle"></i>
                                <p>You haven't submitted any complaints yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Subject</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($complaints as $complaint): ?>
                                            <tr>
                                                <td>#<?php echo $complaint['id']; ?></td>
                                                <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                                <td>
                                                    <?php 
                                                    $type_badge = 'badge-info';
                                                    $type_icon = '';
                                                    switch ($complaint['complaint_type']) {
                                                        case 'hostel_facility':
                                                            $type_icon = '<i class="fas fa-building"></i> ';
                                                            break;
                                                        case 'roommate':
                                                            $type_icon = '<i class="fas fa-user-friends"></i> ';
                                                            break;
                                                        case 'staff':
                                                            $type_icon = '<i class="fas fa-user-tie"></i> ';
                                                            break;
                                                        case 'internet':
                                                            $type_icon = '<i class="fas fa-wifi"></i> ';
                                                            break;
                                                        case 'cleanliness':
                                                            $type_icon = '<i class="fas fa-broom"></i> ';
                                                            break;
                                                        case 'cafeteria':
                                                            $type_icon = '<i class="fas fa-utensils"></i> ';
                                                            break;
                                                        case 'security':
                                                            $type_icon = '<i class="fas fa-shield-alt"></i> ';
                                                            break;
                                                        default:
                                                            $type_icon = '<i class="fas fa-question-circle"></i> ';
                                                    }
                                                    echo '<span class="badge ' . $type_badge . '">' . $type_icon . ucwords(str_replace('_', ' ', $complaint['complaint_type'])) . '</span>'; 
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_class = 'badge-info';
                                                    $status_icon = '<i class="fas fa-clock"></i> ';
                                                    
                                                    switch ($complaint['status']) {
                                                        case 'pending':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'in_progress':
                                                            $status_class = 'badge-info';
                                                            $status_icon = '<i class="fas fa-spinner fa-spin"></i> ';
                                                            break;
                                                        case 'resolved':
                                                            $status_class = 'badge-success';
                                                            $status_icon = '<i class="fas fa-check-circle"></i> ';
                                                            break;
                                                        case 'closed':
                                                            $status_class = 'badge-secondary';
                                                            $status_icon = '<i class="fas fa-lock"></i> ';
                                                            break;
                                                    }
                                                    
                                                    echo '<span class="badge ' . $status_class . '">' . $status_icon . ucfirst(str_replace('_', ' ', $complaint['status'])) . '</span>';
                                                    ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($complaint['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    
                                                    <?php if ($complaint['status'] === 'resolved' && empty($complaint['rating'])): ?>
                                                        <button class="btn btn-sm btn-success" onclick="showFeedbackModal(<?php echo $complaint['id']; ?>)">
                                                            <i class="fas fa-star"></i> Rate
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complaint View Modal -->
<div class="modal" id="complaintModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-comment-alt"></i> Complaint Details</h4>
                <button type="button" class="close" onclick="closeComplaintModal()">&times;</button>
            </div>
            <div class="modal-body" id="complaintContent">
                <!-- Complaint content will be dynamically inserted here -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeComplaintModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal" id="feedbackModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fas fa-star"></i> Rate Your Experience</h4>
                <button type="button" class="close" onclick="closeFeedbackModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="feedbackForm" method="POST" action="complaints.php">
                    <input type="hidden" name="action" value="add_feedback">
                    <input type="hidden" name="complaint_id" id="feedback_complaint_id" value="">
                    
                    <div class="feedback-info">
                        <div class="feedback-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="feedback-text">
                            <p>Your feedback helps us improve our services. Please rate your satisfaction with how your complaint was handled.</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>How satisfied are you with the resolution?</label>
                        <div class="rating-stars">
                            <i class="fas fa-star" data-rating="1"></i>
                            <i class="fas fa-star" data-rating="2"></i>
                            <i class="fas fa-star" data-rating="3"></i>
                            <i class="fas fa-star" data-rating="4"></i>
                            <i class="fas fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="0" required>
                        <div class="rating-text">Select a rating</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback">Your Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Please share your thoughts on how your complaint was handled" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitFeedback()"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
            </div>
        </div>
    </div>
</div>

<script>
// View Complaint Modal Functions
function viewComplaint(complaintId) {
    document.getElementById('complaintContent').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('complaintModal').style.display = 'block';
    
    // Fetch complaint details using AJAX
    fetch('get_complaint.php?id=' + complaintId)
        .then(response => {
            if (!response.ok) {
                // If response is not OK, get text and throw an error to be caught by .catch()
                return response.text().then(text => {
                    // Construct a more informative error message
                    let errorMsg = `Server error: ${response.status} ${response.statusText}.`;
                    // Sanitize text before putting it in <pre> to prevent XSS if it's HTML
                    const sanitizedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    if (text) {
                        errorMsg += ` Server response: <pre>${sanitizedText}</pre>`;
                    }
                    throw new Error(errorMsg);
                });
            }
            return response.text(); // Get raw text first
        })
        .then(text => {
            try {
                const data = JSON.parse(text); // Try to parse as JSON
                if (data.error) {
                    document.getElementById('complaintContent').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                } else {
                    displayComplaintDetails(data);
                }
            } catch (e) {
                // Handle JSON parsing error, display raw text (sanitized)
                const sanitizedText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                document.getElementById('complaintContent').innerHTML = '<div class="alert alert-danger">Error parsing server response. Raw response: <pre>' + sanitizedText + '</pre></div>';
            }
        })
        .catch(error => {
            // This will catch network errors and errors thrown from the .then() blocks
            // The error.message might already contain HTML (<pre> tag), so it's used directly.
            // If it's not pre-formatted, ensure it's properly displayed.
            document.getElementById('complaintContent').innerHTML = '<div class="alert alert-danger">Failed to load complaint details. ' + error.message + '</div>';
        });
}

function displayComplaintDetails(complaint) {
    // Format dates
    const createdDate = new Date(complaint.created_at).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    const updatedDate = new Date(complaint.updated_at).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    
    // Status badge
    let statusClass = 'badge-info';
    let statusIcon = '<i class="fas fa-clock"></i> ';
    switch (complaint.status) {
        case 'pending':
            statusClass = 'badge-warning';
            break;
        case 'in_progress':
            statusClass = 'badge-info';
            statusIcon = '<i class="fas fa-spinner fa-spin"></i> ';
            break;
        case 'resolved':
            statusClass = 'badge-success';
            statusIcon = '<i class="fas fa-check-circle"></i> ';
            break;
        case 'closed':
            statusClass = 'badge-secondary';
            statusIcon = '<i class="fas fa-lock"></i> ';
            break;
    }
    
    // Priority badge
    let priorityClass = 'badge-info';
    let priorityIcon = '<i class="fas fa-flag"></i> ';
    switch (complaint.priority) {
        case 'low':
            priorityClass = 'badge-success';
            break;
        case 'medium':
            priorityClass = 'badge-info';
            break;
        case 'high':
            priorityClass = 'badge-warning';
            break;
        case 'urgent':
            priorityClass = 'badge-danger';
            priorityIcon = '<i class="fas fa-exclamation-triangle"></i> ';
            break;
    }
    
    // Format complaint type
    const complaintType = complaint.complaint_type.replace(/_/g, ' ');
    
    // Attachment link
    let attachmentHTML = '';
    if (complaint.attachment_path) {
        // Extract filename from path
        const fileName = complaint.attachment_path.split('/').pop();
        attachmentHTML = `
            <div class="attachment-section">
                <h5><i class="fas fa-paperclip"></i> Attachment</h5>
                <div class="attachment-preview">
                    <a href="../${complaint.attachment_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i> View Attachment (${fileName})
                    </a>
                </div>
            </div>
        `;
    }
    
    // Resolution section
    let resolutionHTML = '';
    if (complaint.status === 'resolved' || complaint.status === 'closed') {
        resolutionHTML = `
            <div class="resolution-section">
                <h5><i class="fas fa-check-circle"></i> Resolution</h5>
                <p>${complaint.resolution_comments || 'No comments provided.'}</p>
            </div>
        `;
    }
    
    // Feedback section
    let feedbackHTML = '';
    if (complaint.rating) {
        // Generate star rating
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            const starClass = i <= complaint.rating ? 'fas fa-star rated' : 'far fa-star';
            stars += `<i class="${starClass}"></i>`;
        }
        
        feedbackHTML = `
            <div class="feedback-section">
                <h5><i class="fas fa-star"></i> Your Feedback</h5>
                <div class="rating-display">
                    ${stars}
                    <span class="rating-value">${complaint.rating}/5</span>
                </div>
                <p class="feedback-text">${complaint.feedback || 'No feedback provided.'}</p>
            </div>
        `;
    }
    
    // History section
    let historyHTML = '';
    if (complaint.history && complaint.history.length > 0) {
        let historyItems = '';
        complaint.history.forEach(item => {
            const historyDate = new Date(item.created_at).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
            });
            
            let statusIconHistory = '<i class="fas fa-clock"></i> ';
            switch (item.status) {
                case 'in_progress':
                    statusIconHistory = '<i class="fas fa-spinner"></i> ';
                    break;
                case 'resolved':
                    statusIconHistory = '<i class="fas fa-check-circle"></i> ';
                    break;
                case 'closed':
                    statusIconHistory = '<i class="fas fa-lock"></i> ';
                    break;
            }
            
            historyItems += `
                <div class="timeline-item">
                    <div class="timeline-marker ${item.status}">
                        ${statusIconHistory}
                    </div>
                    <div class="timeline-content">
                        <h6>Status changed to ${item.status.replace(/_/g, ' ')}</h6>
                        <p class="timeline-date">${historyDate}</p>
                        ${item.comments ? `<p class="timeline-comment">${item.comments}</p>` : ''}
                    </div>
                </div>
            `;
        });
        
        historyHTML = `
            <div class="history-section">
                <h5><i class="fas fa-history"></i> Status History</h5>
                <div class="timeline">
                    ${historyItems}
                </div>
            </div>
        `;
    }
    
    // Build the complete HTML
    const complaintHTML = `
        <div class="complaint-details">
            <h3>${complaint.subject}</h3>
            
            <div class="complaint-meta">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Complaint ID:</strong> #${complaint.id}</p>
                        <p><strong>Type:</strong> ${complaintType.charAt(0).toUpperCase() + complaintType.slice(1)}</p>
                        <p><strong>Submitted:</strong> ${createdDate}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge ${statusClass}">${statusIcon}${complaint.status.replace(/_/g, ' ')}</span></p>
                        <p><strong>Priority:</strong> <span class="badge ${priorityClass}">${priorityIcon}${complaint.priority}</span></p>
                        <p><strong>Last Updated:</strong> ${updatedDate}</p>
                    </div>
                </div>
            </div>
            
            <div class="complaint-description">
                <h5><i class="fas fa-align-left"></i> Description</h5>
                <div class="description-box">
                    ${complaint.description.replace(/\n/g, '<br>')}
                </div>
            </div>
            
            ${attachmentHTML}
            ${resolutionHTML}
            ${feedbackHTML}
            ${historyHTML}
        </div>
    `;
    
    document.getElementById('complaintContent').innerHTML = complaintHTML;
}

function closeComplaintModal() {
    document.getElementById('complaintModal').style.display = 'none';
}

// Feedback Modal Functions
function showFeedbackModal(complaintId) {
    document.getElementById('feedback_complaint_id').value = complaintId;
    document.getElementById('feedbackModal').style.display = 'block';
    
    // Reset rating
    document.getElementById('rating').value = 0;
    document.querySelectorAll('.rating-stars .fas').forEach(star => {
        star.classList.remove('selected');
    });
    document.querySelector('.rating-text').textContent = 'Select a rating';
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').style.display = 'none';
}

function submitFeedback() {
    const rating = document.getElementById('rating').value;
    if (rating === '0') {
        alert('Please select a rating before submitting.');
        return;
    }
    
    document.getElementById('feedbackForm').submit();
}

// Star Rating System
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-stars .fas');
    const ratingInput = document.getElementById('rating');
    const ratingText = document.querySelector('.rating-text');
    
    const ratingMessages = [
        '',
        'Very Dissatisfied',
        'Dissatisfied',
        'Neutral',
        'Satisfied',
        'Very Satisfied'
    ];
    
    stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            const rating = this.getAttribute('data-rating');
            
            // Fill in stars up to the hovered one
            stars.forEach(s => {
                if (s.getAttribute('data-rating') <= rating) {
                    s.classList.add('hovered');
                } else {
                    s.classList.remove('hovered');
                }
            });
            
            // Update rating text
            ratingText.textContent = ratingMessages[rating];
        });
        
        star.addEventListener('mouseleave', function() {
            stars.forEach(s => {
                s.classList.remove('hovered');
            });
            
            // Restore selected rating text
            const selectedRating = ratingInput.value;
            ratingText.textContent = selectedRating > 0 ? ratingMessages[selectedRating] : 'Select a rating';
        });
        
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            
            // Update selected stars
            stars.forEach(s => {
                if (s.getAttribute('data-rating') <= rating) {
                    s.classList.add('selected');
                } else {
                    s.classList.remove('selected');
                }
            });
            
            // Update rating text
            ratingText.textContent = ratingMessages[rating];
        });
    });
});
</script>

<?php
// Include footer
require_once '../shared/includes/footer.php';
?>
