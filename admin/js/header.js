// Alert message functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alert messages after 5 seconds
    const alertMessage = document.getElementById('alert-message');
    if (alertMessage) {
        setTimeout(function() {
            alertMessage.style.opacity = '0';
            setTimeout(function() {
                alertMessage.style.display = 'none';
            }, 500);
        }, 5000);
    }
});

// Function to close alert messages
function closeAlert() {
    const alert = document.getElementById('alert-message');
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    }
}
