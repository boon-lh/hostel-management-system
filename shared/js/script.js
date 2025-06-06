// Common JavaScript functions for MMU Hostel Management System
function validateForm() {
    const user = document.getElementById("username").value.trim();
    const pass = document.getElementById("password").value.trim();
    const role = document.getElementById("role").value.trim();
    
    if (user === "" || pass === "" || role === "") {
        alert("Please fill in all fields.");
        return false;
    }
    return true;
}

// Check for error parameter in URL
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        document.getElementById('error-message').innerHTML = "Invalid username or password";
    }
    
    // Clear form inputs after submission if there's a success parameter
    if (urlParams.has('success') || urlParams.has('submitted')) {
        clearFormInputs();
    }
    
    // Add form submission event listeners to all forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            // Store the form data was submitted in session storage
            sessionStorage.setItem('formSubmitted', 'true');
        });
    });
    
    // Check if form was submitted in the previous page load
    if (sessionStorage.getItem('formSubmitted') === 'true') {
        clearFormInputs();
        // Clear the flag after handling it
        sessionStorage.removeItem('formSubmitted');
    }
}

// Function to clear all form inputs
function clearFormInputs() {
    document.querySelectorAll('form').forEach(form => {
        form.reset();
        
        // Additionally clear each input field
        form.querySelectorAll('input:not([type=submit]):not([type=button]), textarea, select').forEach(input => {
            input.value = '';
        });
    });
}