/**
 * Students Management JavaScript
 * Hostel Management System - Admin Panel
 * 
 * This file contains all JavaScript functionality for the students page:
 * - Tab switching between student list and finance info
 * - Search functionality for both student and finance tables
 * - Modal handling for student details
 * - AJAX calls for dynamic content loading
 */

// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize search functionality
    initializeSearchFunctionality();
    
    // Initialize modal functionality
    initializeModalFunctionality();
    
    // Initialize export functionality
    initializeExportFunctionality();
    
    console.log('Students.js loaded successfully');
});

/**
 * Initialize search functionality for both tables
 */
function initializeSearchFunctionality() {
    // Student list search
    const studentSearch = document.getElementById('student-search');
    if (studentSearch) {
        studentSearch.addEventListener('keyup', function() {
            filterStudentTable(this.value);
        });
    }
    
    // Finance search
    const financeSearch = document.getElementById('finance-search');
    if (financeSearch) {
        financeSearch.addEventListener('keyup', function() {
            filterFinanceTable(this.value);
        });
    }
}

/**
 * Filter student table based on search term
 * @param {string} searchTerm - The search term to filter by
 */
function filterStudentTable(searchTerm) {
    const table = document.getElementById('students-table');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tr');
    const normalizedSearch = searchTerm.toLowerCase().trim();
    
    // Start from row 1 to skip header row
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.cells;
        let shouldShow = false;
        
        // Search in ID, Name, Course, Email columns
        const searchableColumns = [0, 1, 2, 3]; // Student ID, Name, Course, Email
        
        for (let j of searchableColumns) {
            if (cells[j] && cells[j].textContent.toLowerCase().includes(normalizedSearch)) {
                shouldShow = true;
                break;
            }
        }
        
        row.style.display = shouldShow ? '' : 'none';
    }
}

/**
 * Filter finance table based on search term
 * @param {string} searchTerm - The search term to filter by
 */
function filterFinanceTable(searchTerm) {
    const table = document.getElementById('finance-table');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tr');
    const normalizedSearch = searchTerm.toLowerCase().trim();
    
    // Start from row 1 to skip header row
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.cells;
        let shouldShow = false;
        
        // Search in Student ID and Name columns
        const searchableColumns = [0, 1]; // Student ID, Name
        
        for (let j of searchableColumns) {
            if (cells[j] && cells[j].textContent.toLowerCase().includes(normalizedSearch)) {
                shouldShow = true;
                break;
            }
        }
        
        row.style.display = shouldShow ? '' : 'none';
    }
}

/**
 * Initialize modal functionality
 */
function initializeModalFunctionality() {
    const modal = document.getElementById('student-details-modal');
    const closeBtn = document.querySelector('.modal .close');
    
    if (closeBtn) {
        closeBtn.onclick = function() {
            if (modal) {
                modal.style.display = "none";
            }
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
}

/**
 * Initialize export functionality
 */
function initializeExportFunctionality() {
    const exportButtons = document.querySelectorAll('.btn-export');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const activeTab = document.querySelector('.tab-content.active');
            
            if (activeTab && activeTab.id === 'student-list') {
                exportStudentData();
            } else if (activeTab && activeTab.id === 'finance-info') {
                exportFinanceData();
            }
        });
    });
}

/**
 * Export student data to CSV
 */
function exportStudentData() {
    const table = document.getElementById('students-table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    let csvContent = '';
    
    // Process each row
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        
        cells.forEach((cell, cellIndex) => {
            // Skip actions column (last column)
            if (cellIndex < cells.length - 1) {
                rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            }
        });
        
        if (rowData.length > 0) {
            csvContent += rowData.join(',') + '\n';
        }
    });
    
    // Download CSV
    downloadCSV(csvContent, 'students_list.csv');
}

/**
 * Export finance data to CSV
 */
function exportFinanceData() {
    const table = document.getElementById('finance-table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    let csvContent = '';
    
    // Process each row
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        
        cells.forEach((cell, cellIndex) => {
            // Skip actions column (last column)
            if (cellIndex < cells.length - 1) {
                let cellText = cell.textContent.trim();
                // Clean up status badges
                if (cell.querySelector('.status')) {
                    cellText = cell.querySelector('.status').textContent.trim();
                }
                rowData.push('"' + cellText.replace(/"/g, '""') + '"');
            }
        });
        
        if (rowData.length > 0) {
            csvContent += rowData.join(',') + '\n';
        }
    });
    
    // Download CSV
    downloadCSV(csvContent, 'finance_data.csv');
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

// Global functions used by inline JavaScript in the HTML
/**
 * View student details in modal
 * @param {number} studentId - The student ID
 */
window.viewStudentDetails = function(studentId) {
    const modal = document.getElementById('student-details-modal');
    const contentDiv = document.getElementById('student-details-content');
    
    if (!modal || !contentDiv) {
        console.error('Modal elements not found');
        return;
    }
    
    // Show loading
    showLoading(contentDiv);
    modal.style.display = "block";
    
    // Make AJAX call
    fetch(`get_student_details.php?id=${studentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            contentDiv.innerHTML = data;
        })
        .catch(error => {
            console.error('Error fetching student details:', error);
            showError(contentDiv, 'Error loading student details. Please try again.');
        });
};

/**
 * Redirect to edit student page
 * @param {number} studentId - The student ID
 */
window.editStudent = function(studentId) {
    window.location.href = `edit_student.php?id=${studentId}`;
};

/**
 * Switch to finance tab and filter for specific student
 * @param {number} studentId - The student ID
 */
window.viewFinance = function(studentId) {
    // Switch to finance tab
    const financeTabButton = document.querySelector('[data-tab="finance-info"]');
    if (financeTabButton) {
        financeTabButton.click();
        
        // Wait a bit for the tab to switch, then filter
        setTimeout(() => {
            const financeSearch = document.getElementById('finance-search');
            if (financeSearch) {
                financeSearch.value = studentId;
                filterFinanceTable(studentId);
                financeSearch.focus();
            }
        }, 100);
    }
};

/**
 * View bill details for a student
 * @param {number} studentId - The student ID
 */
window.viewBillDetails = function(studentId) {
    // For now, redirect to a bill details page
    // This can be enhanced with a modal in the future
    window.location.href = `bill_details.php?student_id=${studentId}`;
};

/**
 * View payment receipt for a student
 * @param {number} studentId - The student ID
 */
window.viewPaymentReceipt = function(studentId) {
    // For now, redirect to a payment receipt page
    // This can be enhanced with a modal in the future
    window.location.href = `payment_receipt.php?student_id=${studentId}`;
};
