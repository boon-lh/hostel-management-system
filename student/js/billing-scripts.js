/**
 * Billing Scripts for MMU Hostel Management System
 * Contains functions for managing billing tabs and other common functionality
 */

// When document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Payment Modal Functions
    window.showPaymentModal = function(billId, remainingAmount) {
        paymentHandler.showPaymentModal(billId, remainingAmount);
    };

    // Refund Modal Functions
    window.showRefundModal = function(paymentId, maxAmount) {
        paymentHandler.showRefundModal(paymentId, maxAmount);
    };

    // Invoice Functions
    window.viewInvoice = function(invoice) {
        const studentName = document.getElementById('student-name').value;
        const studentId = document.getElementById('student-id').value;
        invoiceHandler.viewInvoice(invoice, studentName, studentId);
    };

    window.printInvoice = function(invoice) {
        const studentName = document.getElementById('student-name').value;
        const studentId = document.getElementById('student-id').value;
        invoiceHandler.printInvoice(invoice, studentName, studentId);
    };

    // Tab Navigation
    const tabLinks = document.querySelectorAll('.nav-link');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // This is handled by href="?tab=x" now, but keep it for any direct interactions
        });
    });
});
