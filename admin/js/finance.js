// Finance Management JavaScript

/**
 * View bill details for a specific student
 * @param {number} studentId - The ID of the student
 */
function viewBillDetails(studentId) {
    window.location.href = `bill_details.php?student_id=${studentId}`;
}

/**
 * Submit payment for a student
 * @param {Event} event - The form submit event
 * @param {number} studentId - The ID of the student
 */
async function submitPayment(event, studentId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {
        student_id: studentId,
        amount: formData.get('amount'),
        payment_date: formData.get('payment_date'),
        payment_method: formData.get('payment_method'),
        reference: formData.get('reference')
    };

    try {
        const response = await fetch('record_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                // Close modal and refresh page
                closePaymentModal();
                window.location.reload();
            } else {
                alert(result.message || 'Failed to record payment');
            }
        } else {
            throw new Error('Network response was not ok');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to record payment. Please try again.');
    }
}

// Initialize tooltips if needed
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
});
