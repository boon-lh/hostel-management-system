/**
 * Payment Handler for MMU Hostel Management System
 * Manages payment and refund modals and related functionality
 */

class PaymentHandler {
    constructor() {
        this.paymentModal = document.getElementById('paymentModal');
        this.refundModal = document.getElementById('refundModal');
    }

    /**
     * Show the payment modal with validation
     * @param {number} billId - The ID of the bill
     * @param {number} remainingAmount - The remaining amount to be paid
     */
    showPaymentModal(billId, remainingAmount) {
        // Validate bill ID
        if (!billId || billId <= 0) {
            alert('Error: Invalid bill selected. Please refresh the page and try again.');
            return;
        }
        
        document.getElementById('bill_id').value = billId;
        document.getElementById('remainingAmount').textContent = remainingAmount.toFixed(2);
        document.getElementById('amount').value = remainingAmount.toFixed(2);
        document.getElementById('amount').max = remainingAmount;
        
        // Reset form fields except bill_id and amount
        document.getElementById('payment_method').value = '';
        document.getElementById('reference_number').value = '';
        document.getElementById('notes').value = '';
        
        // Show the modal
        document.getElementById('paymentModal').style.display = 'block';
    }

    /**
     * Close the payment modal
     */
    closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }    /**
     * Submit the payment form with validation
     */
    submitPayment() {
        // Validate bill_id
        const billId = document.getElementById('bill_id').value;
        if (!billId || billId === '0' || billId === 0) {
            alert('Error: Invalid bill selected. Please refresh the page and try again.');
            return;
        }
        
        // Validate amount
        const amount = document.getElementById('amount').value;
        if (!amount || parseFloat(amount) <= 0) {
            alert('Please enter a valid amount.');
            return;
        }
        
        // Validate payment method
        const paymentMethod = document.getElementById('payment_method').value;
        if (!paymentMethod) {
            alert('Please select a payment method.');
            return;
        }
        
        try {
            // Check the form submission target includes the bill ID in the URL
            const form = document.getElementById('paymentForm');
            if (!form.action.includes('bill_id=')) {
                // Ensure bill_id is added to the action URL if needed
                const separator = form.action.includes('?') ? '&' : '?';
                form.action += `${separator}bill_id=${billId}`;
            }
            
            // All validations passed, submit the form
            form.submit();
        } catch (error) {
            console.error('Error submitting payment form:', error);
            alert('There was an error processing your payment. Please try again.');
        }
    }

    /**
     * Show the refund modal
     * @param {number} paymentId - The ID of the payment
     * @param {number} maxAmount - The maximum refundable amount
     */
    showRefundModal(paymentId, maxAmount) {
        document.getElementById('refund_payment_id').value = paymentId;
        document.getElementById('maxRefundAmount').textContent = maxAmount.toFixed(2);
        document.getElementById('refund_amount').value = maxAmount.toFixed(2);
        document.getElementById('refund_amount').max = maxAmount;
        
        // Show the modal
        document.getElementById('refundModal').style.display = 'block';
    }

    /**
     * Close the refund modal
     */
    closeRefundModal() {
        document.getElementById('refundModal').style.display = 'none';
    }

    /**
     * Submit the refund form
     */
    submitRefund() {
        document.getElementById('refundForm').submit();
    }
}

// Create a global instance of the PaymentHandler
const paymentHandler = new PaymentHandler();
