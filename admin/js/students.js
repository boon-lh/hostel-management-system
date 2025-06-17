/**
 * Students Management JavaScript
 * Hostel Management System - Admin Panel
 * 
 * This file contains all JavaScript functionality for the students page:
 * - CRUD operations for students
 * - Search functionality
 * - Modal handling for student details
 * - Data export capabilities
 */
console.log('Students.js file is being loaded - ' + new Date().toLocaleTimeString());

// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Students.js loading...');
    
    // Initialize search functionality
    initializeSearchFunctionality();

    // Initialize form validation if on create student page
    if (document.querySelector('.student-form')) {
        initializeFormValidation();
    }
    
    // Add export button functionality
    const exportButton = document.querySelector('.btn-export');
    if (exportButton) {
        exportButton.addEventListener('click', exportStudentData);
    }
    
    // Setup table hover effects
    setupTableHoverEffects();
    
    // Restore search filter from session storage
    const savedSearch = sessionStorage.getItem('studentSearchTerm');
    if (savedSearch) {
        const searchInput = document.getElementById('student-search');
        if (searchInput) {
            searchInput.value = savedSearch;
            filterStudentTable(savedSearch);
        }
    }
    
    // Initialize sort functionality
    initializeSortFunctionality();
    
    console.log('Students.js loaded successfully');
});

// Utility function for email validation
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Initialize search functionality for student table
 */
function initializeSearchFunctionality() {
    const searchInput = document.getElementById('student-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value;
        sessionStorage.setItem('studentSearchTerm', searchTerm);
        filterStudentTable(searchTerm);
    });
}

/**
 * Filter student table based on search term
 * @param {string} searchTerm - The search term to filter by
 */
function filterStudentTable(searchTerm) {
    const table = document.querySelector('.student-table');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchTerm.toLowerCase();
    let found = false;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const match = text.includes(searchLower);
        row.style.display = match ? '' : 'none';
        if (match) found = true;
    });

    // Show/hide no results message
    let noResults = table.querySelector('.no-results');
    if (!found && !noResults) {
        const tbody = table.querySelector('tbody');
        noResults = document.createElement('tr');
        noResults.className = 'no-results';
        noResults.innerHTML = `<td colspan="6" class="text-center">No students found matching "${searchTerm}"</td>`;
        tbody.appendChild(noResults);
    } else if (found && noResults) {
        noResults.remove();
    }
}

// Note: Unused function filterFinanceTable was removed

// Note: Unused functions initializeModalFunctionality and initializeExportFunctionality were removed

/**
 * Export student data to CSV
 */
function exportStudentData() {
    try {
        const table = document.querySelector('.student-table');
        if (!table) throw new Error('Student table not found');

        const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => 
            row.style.display !== 'none' && !row.classList.contains('no-results')
        );

        if (rows.length === 0) {
            throw new Error('No students to export');
        }

        const headers = Array.from(table.querySelectorAll('thead th'))
            .map(th => th.textContent.trim())
            .filter(header => header !== 'Actions');

        const csvContent = [
            headers.join(','),
            ...rows.map(row => 
                Array.from(row.querySelectorAll('td'))
                    .slice(0, -1) // Remove actions column
                    .map(cell => `"${cell.textContent.trim()}"`)
                    .join(',')
            )
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', 'students.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        handleError(error, 'exporting student data');
    }
}

/**
 * Download CSV file
 * @param {string} csvContent - The CSV content
 * @param {string} filename - The filename for download
 */
function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

/**
 * Show loading indicator
 * @param {HTMLElement} element - Element to show loading in
 */
function showLoading(element) {
    if (element) {
        element.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

/**
 * Show error message
 * @param {HTMLElement} element - Element to show error in
 * @param {string} message - Error message to display
 */
function showError(element, message) {
    if (element) {
        element.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ${message}</div>`;
    }
}

/**
 * Setup export button functionality
 */
function setupExportButton() {
    const exportButton = document.querySelector('.btn-export');
    if (exportButton) {
        exportButton.addEventListener('click', exportStudentData);
    }
}

/**
 * Setup table row hover effects
 */
function setupTableHoverEffects() {
    const tableRows = document.querySelectorAll('.student-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.backgroundColor = '#f5f5f5';
            row.style.cursor = 'pointer';
        });
        row.addEventListener('mouseleave', () => {
            row.style.backgroundColor = '';
        });
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const form = document.querySelector('.student-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let isValid = true;
        const errorMessages = [];

        // Clear previous validation states
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        // Name validation
        const nameInput = form.querySelector('[name="name"]');
        if (!nameInput?.value.trim()) {
            isValid = false;
            showFieldError(nameInput, 'Name is required');
        } else if (nameInput.value.length > 100) {
            isValid = false;
            showFieldError(nameInput, 'Name must be less than 100 characters');
        }

        // Contact number validation
        const contactInput = form.querySelector('[name="contact_no"]');
        if (!contactInput?.value.trim()) {
            isValid = false;
            showFieldError(contactInput, 'Contact Number is required');
        } else {
            const cleanNumber = contactInput.value.replace(/[-\s]/g, '');
            if (!/^\d{10,15}$/.test(cleanNumber)) {
                isValid = false;
                showFieldError(contactInput, 'Contact Number must be 10-15 digits');
            }
        }

        // Email validation
        const emailInput = form.querySelector('[name="email"]');
        if (!emailInput?.value.trim()) {
            isValid = false;
            showFieldError(emailInput, 'Email is required');
        } else if (!isValidEmail(emailInput.value)) {
            isValid = false;
            showFieldError(emailInput, 'Invalid email format');
        } else if (emailInput.value.length > 100) {
            isValid = false;
            showFieldError(emailInput, 'Email must be less than 100 characters');
        }

        // Address validation
        const addressInput = form.querySelector('[name="address"]');
        if (!addressInput?.value.trim()) {
            isValid = false;
            showFieldError(addressInput, 'Address is required');
        } else if (addressInput.value.length > 255) {
            isValid = false;
            showFieldError(addressInput, 'Address must be less than 255 characters');
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Show error message for a specific field
function showFieldError(element, message) {
    if (!element) return;
    
    element.classList.add('is-invalid');
    
    // Create error message element if it doesn't exist
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    // Remove any existing error message
    const existingError = element.nextElementSibling;
    if (existingError?.classList.contains('invalid-feedback')) {
        existingError.remove();
    }
    
    // Insert error message after the input
    element.parentNode.insertBefore(errorDiv, element.nextSibling);
}

/**
 * Handle server errors
 * @param {Error} error - Error object
 * @param {string} customMessage - Custom error message to show user
 */
function handleError(error, customMessage = 'An error occurred. Please try again.') {
    console.error('Error:', error);
    alert(customMessage);
}

// Global functions used by inline JavaScript in the HTML
/**
 * View student details in modal
 * @param {number} studentId - The student ID
 */
window.viewStudentDetails = async function(studentId) {
    try {
        // Show loading indicator
        const modalBody = document.querySelector('#studentDetailsModal .modal-body');
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        // Show modal while loading
        const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
        modal.show();

        // Fetch student details
        const response = await fetch(`get_student_details.php?id=${studentId}`);
        if (!response.ok) {
            throw new Error('Failed to fetch student details');
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to load student details');
        }

        const { student, emergency_contact, hostel_info, finance_summary, complaints_summary } = data;
        
        // Update Basic Info
        document.querySelector('.student-name').textContent = student.name;
        document.querySelector('.student-id').textContent = `Student ID: ${student.id}`;
        document.querySelector('.student-gender').textContent = student.gender;
        document.querySelector('.student-dob').textContent = formatDate(student.dob);
        document.querySelector('.student-citizenship').textContent = student.citizenship;
        document.querySelector('.student-ic').textContent = student.ic_number;
        document.querySelector('.student-contact').textContent = student.contact_no;
        document.querySelector('.student-email').textContent = student.email;
        document.querySelector('.student-course').textContent = student.course;
        document.querySelector('.student-address').textContent = student.address;

        // Update Emergency Contact Info
        if (emergency_contact) {
            document.querySelector('.emergency-contact-name').textContent = emergency_contact.name;
            document.querySelector('.emergency-contact-relationship').textContent = emergency_contact.relationship;
            document.querySelector('.emergency-contact-phone').textContent = emergency_contact.contact_no;
            document.querySelector('.emergency-contact-email').textContent = emergency_contact.email;
        }

        // Update Hostel Info
        if (hostel_info) {
            document.querySelector('.hostel-room').textContent = hostel_info.room_number || 'Not Assigned';
            document.querySelector('.hostel-block').textContent = hostel_info.block_name || 'Not Assigned';
            document.querySelector('.hostel-room-type').textContent = hostel_info.room_type || 'N/A';
        }

        // Update Financial Summary
        if (finance_summary) {
            document.querySelector('.finance-total-bills').textContent = finance_summary.total_bills;
            document.querySelector('.finance-total-billed').textContent = `RM ${parseFloat(finance_summary.total_billed).toFixed(2)}`;
            document.querySelector('.finance-total-paid').textContent = `RM ${parseFloat(finance_summary.total_paid).toFixed(2)}`;
            document.querySelector('.finance-outstanding').textContent = `RM ${parseFloat(finance_summary.outstanding_balance).toFixed(2)}`;
        }

        // Update Complaints Summary
        if (complaints_summary) {
            document.querySelector('.complaints-total').textContent = complaints_summary.complaint_count;
            document.querySelector('.complaints-pending').textContent = complaints_summary.pending_complaints;
        }

        // Setup edit button
        const editBtn = document.querySelector('.edit-student-btn');
        editBtn.onclick = () => {
            document.getElementById('studentDetailsModal').querySelector('[data-bs-dismiss="modal"]').click();
            editStudent(student.id);
        };

    } catch (error) {
        console.error('Error loading student details:', error);
        const modalBody = document.querySelector('#studentDetailsModal .modal-body');
        modalBody.innerHTML = `<div class="alert alert-danger">Error loading student details: ${error.message}</div>`;
    }
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return 'Not provided';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid date';
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (error) {
        console.error('Error formatting date:', error);
        return 'Error formatting date';
    }
}

/**
 * Redirect to edit student page
 * @param {number} studentId - The student ID
 */
window.editStudent = function(studentId) {
    try {
        // Input validation
        if (!studentId || isNaN(studentId)) {
            throw new Error('Invalid student ID');
        }

        // Save current page state
        const currentSearchTerm = document.getElementById('student-search')?.value;
        if (currentSearchTerm) {
            sessionStorage.setItem('studentSearchTerm', currentSearchTerm);
        }
        
        // Navigate to edit page
        window.location.href = `edit_student.php?id=${studentId}`;
    } catch (error) {
        handleError(error, 'editing student');
    }
};

/**
 * Delete student record after confirmation
 * @param {number} studentId - The student ID
 * @param {string} studentName - The student name (for confirmation message)
 */
window.deleteStudent = function(studentId, studentName) {
    // Confirm deletion
    if (confirm(`Are you sure you want to delete ${studentName}? This action cannot be undone.`)) {
        console.log(`Attempting to delete student ID: ${studentId}`);
        
        // Show loading overlay or indicator
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Deleting...</span></div>';
        document.body.appendChild(overlay);
        
        // Create form data for the request
        const formData = new FormData();
        formData.append('id', studentId);
        
        // Make AJAX call to delete the student
        fetch('delete_student.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Remove loading overlay
            document.body.removeChild(overlay);
            
            if (data.success) {
                // Show success message
                alert('Student deleted successfully.');
                
                // Remove row from table
                const row = document.querySelector(`tr[data-student-id="${studentId}"]`) || 
                            document.querySelector(`button[onclick*="deleteStudent(${studentId}"]`).closest('tr');
                
                if (row) {
                    row.remove();
                } else {
                    // If row can't be found, reload the page
                    window.location.reload();
                }
            } else {
                // Show error message
                alert('Error deleting student: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            // Remove loading overlay
            if (document.body.contains(overlay)) {
                document.body.removeChild(overlay);
            }
            
            console.error('Error:', error);
            alert('An error occurred while deleting the student. Please try again.');
        });
    }
};

/**
 * Sort functionality for student table
 */
function initializeSortFunctionality() {
    const sortSelect = document.getElementById('sort-select');
    const sortDirectionBtn = document.getElementById('sort-direction');
    
    if (!sortSelect || !sortDirectionBtn) return;
    
    // Get current sort parameters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort') || 'id';
    const currentDirection = urlParams.get('direction') || 'ASC';
    
    // Set initial values based on URL parameters
    sortSelect.value = currentSort;
    updateSortIcon(sortDirectionBtn, currentDirection);
    
    // Add event listeners
    sortSelect.addEventListener('change', () => {
        applySorting(sortSelect.value, currentDirection);
    });
    
    sortDirectionBtn.addEventListener('click', () => {
        const newDirection = currentDirection === 'ASC' ? 'DESC' : 'ASC';
        applySorting(currentSort, newDirection);
    });
}

/**
 * Update the sort direction icon
 * @param {HTMLElement} button - The sort direction button
 * @param {string} direction - The sort direction ('ASC' or 'DESC')
 */
function updateSortIcon(button, direction) {
    const icon = button.querySelector('i');
    if (direction === 'ASC') {
        icon.className = 'fas fa-sort-up';
        button.title = 'Sort Ascending';
    } else {
        icon.className = 'fas fa-sort-down';
        button.title = 'Sort Descending';
    }
}

/**
 * Apply sorting to the table and reload the page
 * @param {string} column - The column to sort by
 * @param {string} direction - The sort direction ('ASC' or 'DESC')
 */
function applySorting(column, direction) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', column);
    url.searchParams.set('direction', direction);
    window.location.href = url.toString();
}
