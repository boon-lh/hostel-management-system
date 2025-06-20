/**
 * Complaint management JavaScript functions
 * Contains all the functions for complaint viewing, deletion, feedback, and form validation
 */

// Initialize tooltips when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips if Bootstrap is loaded
    if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    } else {
        // Basic tooltip implementation if Bootstrap is not available
        const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseover', function() {
                const title = this.getAttribute('title');
                if (!title) return;
                
                const tooltipDiv = document.createElement('div');
                tooltipDiv.className = 'custom-tooltip';
                tooltipDiv.textContent = title;
                
                document.body.appendChild(tooltipDiv);
                
                const rect = this.getBoundingClientRect();
                tooltipDiv.style.position = 'absolute';
                tooltipDiv.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                tooltipDiv.style.left = (rect.left + window.scrollX + (rect.width / 2) - (tooltipDiv.offsetWidth / 2)) + 'px';
                tooltipDiv.style.backgroundColor = '#000';
                tooltipDiv.style.color = '#fff';
                tooltipDiv.style.padding = '5px 10px';
                tooltipDiv.style.borderRadius = '3px';
                tooltipDiv.style.fontSize = '12px';
                tooltipDiv.style.zIndex = '1000';
                
                this.addEventListener('mouseout', function() {
                    document.body.removeChild(tooltipDiv);
                }, { once: true });
            });
        });
    }
});

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

// Delete Complaint Functions
function confirmDeleteComplaint(complaintId) {
    // Set the complaint ID to the hidden input field in the delete confirmation form
    document.getElementById('delete_complaint_id').value = complaintId;
    
    // Show the delete confirmation modal - works with both Bootstrap 4 and 5
    var modal = document.getElementById('deleteConfirmationModal');
    
    // Try Bootstrap 5 method first
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else if (typeof $ !== 'undefined' && $.fn.modal) {
        // Fallback to jQuery for Bootstrap 4
        $(modal).modal('show');
    } else {
        // Direct DOM manipulation as a fallback
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Create backdrop if it doesn't exist
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
    }
}

// Close delete confirmation modal
function closeDeleteModal() {
    var modal = document.getElementById('deleteConfirmationModal');
    
    // Try Bootstrap 5 method first
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    } else if (typeof $ !== 'undefined' && $.fn.modal) {
        // Fallback to jQuery for Bootstrap 4
        $(modal).modal('hide');
    } else {
        // Direct DOM manipulation as a fallback
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        var backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            document.body.removeChild(backdrop);
        }
    }
}

// Form validation functions
function validateComplaintForm() {
    const subject = document.getElementById('subject');
    const complaintType = document.getElementById('complaint_type');
    const description = document.getElementById('description');
    
    let isValid = true;
    let errorMessages = [];
    
    // Reset previous error states
    [subject, complaintType, description].forEach(field => {
        if (field) {
            field.classList.remove('is-invalid');
        }
    });
    
    // Validate subject
    if (!subject || !subject.value.trim()) {
        isValid = false;
        errorMessages.push('Subject is required');
        if (subject) subject.classList.add('is-invalid');
    }
    
    // Validate complaint type
    if (!complaintType || !complaintType.value) {
        isValid = false;
        errorMessages.push('Issue Type is required');
        if (complaintType) complaintType.classList.add('is-invalid');
    }
    
    // Validate description
    if (!description || !description.value.trim()) {
        isValid = false;
        errorMessages.push('Description is required');
        if (description) description.classList.add('is-invalid');
    }
    
    // Show errors if any
    if (!isValid) {
        alert('Please fix the following errors:\n' + errorMessages.join('\n'));
        console.log('Validation errors:', errorMessages);
    }
    
    return isValid;
}

// Initialize event handlers when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize star rating system
    initializeStarRating();
    
    // Initialize complaint form
    initializeComplaintForm();
});

// Star Rating System
function initializeStarRating() {
    const stars = document.querySelectorAll('.rating-stars .fas');
    const ratingInput = document.getElementById('rating');
    const ratingText = document.querySelector('.rating-text');
    
    if (!stars.length || !ratingInput || !ratingText) return;
    
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
}

// Complaint Form Initialization
function initializeComplaintForm() {
    const complaintForm = document.getElementById('complaintForm');
    
    if (complaintForm) {
        console.log('Complaint form found - adding handlers');
        
        // Add custom submit handler with debug
        complaintForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            
            // Validate form
            if (!validateComplaintForm()) {
                console.log('Form validation failed - preventing submission');
                e.preventDefault();
                return false;
            }
            
            // Add loading state to button
            const submitButton = document.getElementById('submitComplaintBtn');
            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitButton.disabled = true;
            }
            
            console.log('Form is valid - proceeding with submission');
            // Let the form submit normally
            return true;
        });
        
        // Reset form on page load to ensure clean state
        complaintForm.reset();
    } else {
        console.error('Complaint form not found by ID');
    }
}
