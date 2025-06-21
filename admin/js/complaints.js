/**
 * Admin Complaints Management JavaScript
 * Handles AJAX operations for updating complaint status
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Complaints JS initialized');
    
    // Initialize update status form if it exists
    initializeUpdateStatusForm();
    
    // Test the AJAX handler connectivity
    testAjaxHandler();
});

/**
 * Initialize the update status form with AJAX submission
 */
function initializeUpdateStatusForm() {
    // Main update status form
    const updateStatusForm = document.getElementById('update-status-form');
    if (updateStatusForm) {
        console.log('Main update form found, attaching event listener');
        updateStatusForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Main update form submitted');
            updateComplaintStatus(this);
        });
    }
    
    // Also attach to modal form if it exists
    // This works for dynamically created forms by using event delegation
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'modal-update-status-form') {
            e.preventDefault();
            console.log('Modal update form submitted via delegation');
            updateComplaintStatus(e.target);
        }
    });

    // Add click listeners to quick status buttons
    document.querySelectorAll('.quick-status-update').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const complaintId = this.getAttribute('data-id');
            const newStatus = this.getAttribute('data-status');
            
            // Show confirmation dialog
            if (confirm(`Are you sure you want to change the status to ${newStatus.replace('_', ' ')}?`)) {
                // Set values in the quick update form and submit
                document.getElementById('quick_complaint_id').value = complaintId;
                document.getElementById('quick_new_status').value = newStatus;
                document.getElementById('quick_comments').value = `Status changed to ${newStatus.replace('_', ' ')}`;
                
                // Submit the quick update form
                const quickForm = document.getElementById('quick-update-form');
                updateComplaintStatus(quickForm);
            }
        });
    });
}

// Display notification function
      // Create status history HTML with error handling for missing data
    let statusHistoryHTML = '';
    if (complaint.status_history && complaint.status_history.length > 0) {
        statusHistoryHTML = '<div class="status-history mt-4"><h5>Status History</h5><div class="status-timeline">';
        
        // Use a safe loop to process history items
        (complaint.status_history || []).forEach(history => {
            try {
                // Format history date with error handling
                let historyDate = 'Unknown date';
                if (history.created_at) {
                    try {
                        historyDate = new Date(history.created_at).toLocaleString();
                    } catch (e) {
                        console.error('Error formatting date:', e);
                    }
                }
                
                // Icon based on status with default
                let iconClass = 'fa-info-circle text-info';
                if (history.status) {
                    switch(history.status) {
                        case 'pending': iconClass = 'fa-clock text-warning'; break;
                        case 'in_progress': iconClass = 'fa-tools text-info'; break;
                        case 'resolved': iconClass = 'fa-check-circle text-success'; break;
                        case 'closed': iconClass = 'fa-times-circle text-secondary'; break;
                    }
                }
                
                // Generate the HTML with safe fallbacks
                statusHistoryHTML += `
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas ${iconClass}"></i>
                        </div>                    <div class="timeline-content">
                            <h6>${history.status ? history.status.replace('_', ' ') : 'Status update'} <small class="text-muted">- ${historyDate}</small></h6>
                            ${history.changed_by_name ? `<p class="small">By: ${history.changed_by_name}</p>` : ''}
                            ${history.comments ? `<p>${history.comments}</p>` : ''}
                        </div>
                    </div>`;            } catch (e) {
                console.error('Error processing history item:', e);
                statusHistoryHTML += `
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Status update</h6>
                            <p>Error displaying this status update</p>
                        </div>
                    </div>`;
            }
        });
          statusHistoryHTML += '</div></div>';
    } else {
        // Provide a fallback if status history is missing
        statusHistoryHTML = `
            <div class="status-history mt-4">
                <h5>Status History</h5>
                <div class="alert alert-info">
                    No status history available for this complaint.
                </div>
            </div>
        `;
    }
    
    // Build the status update form if complaint is not closed
    let updateFormHTML = '';
    if (complaint.status !== 'closed') {
        // Determine available status options based on current status
        let statusOptions = '';
        
        if (complaint.status === 'pending') {
            statusOptions = `
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            `;
        } else if (complaint.status === 'in_progress') {
            statusOptions = `
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            `;
        } else if (complaint.status === 'resolved') {
            statusOptions = `
                <option value="closed">Closed</option>
            `;
        }
        
        // Only show form if there are status options available
        if (statusOptions) {
            updateFormHTML = `                <div class="update-status-section mt-4">
                    <h5>Update Status</h5>
                    <form id="modal-update-status-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="complaint_id" value="${complaint.id}">
                        <div class="mb-3">
                            <label for="new_status_modal" class="form-label">New Status</label>
                            <select class="form-select" id="new_status_modal" name="new_status" required>
                                <option value="">-- Select Status --</option>
                                ${statusOptions}
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comments_modal" class="form-label">Comments</label>
                            <textarea class="form-control" id="comments_modal" name="comments" rows="2" required 
                                placeholder="Please provide details about this status update"></textarea>
                        </div>
                          <div class="d-flex justify-content-end">
                            <button type="submit" class="filter-btn">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </div>
                    </form>
                    <div class="mt-3">
                        <small class="text-muted">If update doesn't work, try using the <a href="direct_status_update.php" target="_blank">direct update tool</a>.</small>
                    </div>
                </div>
            `;
        }
    }
    
    // Attachment section
    let attachmentHTML = '';
    if (complaint.attachment_path) {
        const ext = complaint.attachment_path.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
        
        attachmentHTML = `
            <div class="attachment-section mt-4">
                <h5>Attachment</h5>
                <div class="attachment-container">`;
                
        if (isImage) {
            attachmentHTML += `
                <a href="../${complaint.attachment_path}" target="_blank">
                    <img src="../${complaint.attachment_path}" class="img-thumbnail" style="max-height: 100px;">
                </a>`;
        } else {
            attachmentHTML += `
                <div class="document-preview">
                    <i class="fas fa-file-${ext === 'pdf' ? 'pdf' : (ext === 'doc' || ext === 'docx' ? 'word' : 'alt')} fa-2x"></i>
                    <p>Click to view document</p>
                </div>`;
        }
                
        attachmentHTML += `
                    <a href="../${complaint.attachment_path}" class="btn btn-sm btn-outline-primary mt-2" target="_blank">
                        <i class="fas fa-download"></i> Download Attachment
                    </a>
                </div>
            </div>`;
    }
    
    // Feedback section
    let feedbackHTML = '';
    if (complaint.rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="fas fa-star ${i <= complaint.rating ? 'text-warning' : 'text-muted'}"></i> `;
        }
        
        feedbackHTML = `
            <div class="feedback-section mt-4">
                <h5>Student Feedback</h5>
                <div class="rating mb-2">
                    ${stars} <span class="ms-2">${complaint.rating}/5</span>
                </div>
                <p>${complaint.feedback || 'No feedback text provided.'}</p>
            </div>
        `;
    }
    
    // Build the complete HTML
    const complaintHTML = `
        <div class="complaint-details">
            <div class="row">
                <div class="col-md-6">
                    <h5>Complaint Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <th>Subject:</th>
                            <td>${complaint.subject}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td><span class="status status-in-progress">${complaint.complaint_type.replace('_', ' ')}</span></td>
                        </tr>
                        <tr>
                            <th>Priority:</th>
                            <td><span class="status ${priorityClass}">${complaint.priority}</span></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><span class="status ${statusClass}">${complaint.status.replace('_', ' ')}</span></td>
                        </tr>
                        <tr>
                            <th>Submitted:</th>
                            <td>${createdDate}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Student Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <th>Name:</th>
                            <td>${complaint.student_name}</td>
                        </tr>
                        <tr>
                            <th>Contact:</th>
                            <td>${complaint.contact_no || 'Not available'}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>${complaint.email || 'Not available'}</td>
                        </tr>                        <tr>
                            <th>Room:</th>
                            <td>${complaint.room_number && complaint.block ? `Block ${complaint.block}, Room ${complaint.room_number}` : 'Not assigned'}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="description-section mt-4">
                <h5>Description</h5>
                <div class="complaint-description">
                    ${complaint.description.replace(/\n/g, '<br>')}
                </div>
            </div>
            
            ${attachmentHTML}
            ${feedbackHTML}
            ${statusHistoryHTML}
            ${updateFormHTML}
        </div>
    `;    // Set the content
    modalBody.innerHTML = complaintHTML;
      // Initialize the form in the modal if it exists
    const modalForm = document.getElementById('modal-update-status-form');
    if (modalForm) {
        console.log('Modal form found in displayed complaint, attaching event listener');
        modalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Log the form values
            const complaintId = this.querySelector('[name="complaint_id"]').value;
            const newStatus = this.querySelector('[name="new_status"]').value;
            const comments = this.querySelector('[name="comments"]').value;
            console.log(`Submitting from modal: ID=${complaintId}, Status=${newStatus}, Comments=${comments}`);
            
            // Show submission message in the modal
            const formArea = modalForm.parentNode;
            const statusDiv = document.createElement('div');
            statusDiv.className = 'alert alert-info mt-3';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting update...';
            formArea.appendChild(statusDiv);
            
            // Call the update function with a callback to show result in modal
            updateComplaintStatus(modalForm, true);
        });
    }
} catch (error) {
    console.error('Error displaying complaint details:', error);
    const modalBody = document.getElementById('complaintModalContent');
    modalBody.innerHTML = `
        <div class="alert alert-danger">
            <h5>Error Displaying Complaint Details</h5>
            <p>There was an error processing the complaint details: ${error.message}</p>
            <p>Please try refreshing the page or contact support if this issue persists.</p>
        </div>
    `;
}
}

/**
 * Update complaint status via AJAX
 * @param {HTMLFormElement} form - The form element containing status update data
 * @param {boolean} isModal - Whether this is called from a modal form
 */
function updateComplaintStatus(form, isModal = false) {
    console.log('updateComplaintStatus called with form:', form);
    
    // Verify form data
    const complaintId = form.querySelector('[name="complaint_id"]').value;
    const newStatus = form.querySelector('[name="new_status"]').value;
    
    console.log(`Updating complaint #${complaintId} to status "${newStatus}"`);
    
    if (!complaintId || !newStatus) {
        showNotification('error', 'Missing complaint ID or status');
        return;
    }
    
    // Disable the submit button to prevent multiple submissions
    const submitBtn = form.querySelector('[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Update';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    }
    
    // Get form data
    const formData = new FormData(form);
    
    // Log the data being sent
    for (let [key, value] of formData.entries()) {
        console.log(`Form data: ${key} = ${value}`);
    }
    
    // First try the direct status update tool for reliability
    const directUpdateData = new FormData();
    directUpdateData.append('complaint_id', complaintId);
    directUpdateData.append('new_status', newStatus);
    
    // Try both approaches - first the direct tool, then the AJAX handler
    fetch('direct_status_update.php', {
        method: 'POST',
        body: directUpdateData
    })
    .then(() => {
        console.log('Direct update completed, now trying AJAX handler');
        
        // Now send request to AJAX handler
        return fetch('complaint_ajax_handler.php', {
            method: 'POST',
            body: formData
        });
    })
    .then(response => {
        console.log('AJAX response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                // If response isn't valid JSON, create a default error object
                return { 
                    success: false, 
                    message: 'Invalid JSON response from server. Check console for details.' 
                };
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('success', data.message || 'Status updated successfully');
            
            // Redirect to refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Show error message but refresh anyway since direct update may have worked
            showNotification('error', data.message || 'An error occurred with AJAX update - page will refresh to check status');
            
            // Refresh anyway since the direct update may have worked
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        // Show error message but refresh anyway
        showNotification('error', `Error: ${error.message} - page will refresh to check status`);
        
        // Reset button if operation fails
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        
        // Refresh anyway after a delay
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    });
}

/**
 * Show notification message
 */
function showNotification(type, message) {
    const notificationContainer = document.getElementById('notificationContainer');
    if (!notificationContainer) {
        // Create notification container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert ${type === 'success' ? 'alert-success' : 'alert-danger'} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to container
    document.getElementById('notificationContainer').appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 1000);
    }, 5000);
}

/**
 * Test the AJAX handler connectivity
 */
function testAjaxHandler() {
    console.log('Testing AJAX handler connectivity...');
    
    fetch('complaint_ajax_handler.php?action=test')
        .then(response => {
            console.log('Test response status:', response.status);
            if (!response.ok) {
                throw new Error(`Test failed with status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('AJAX test successful:', data);
        })
        .catch(error => {
            console.error('AJAX test failed:', error);
            // Add a hidden message that indicates the AJAX handler is not working
            const messageDiv = document.createElement('div');
            messageDiv.style.display = 'none';
            messageDiv.id = 'ajax-test-failure';
            messageDiv.textContent = `AJAX handler test failed: ${error.message}`;
            document.body.appendChild(messageDiv);
        });
}
