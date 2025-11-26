// assets/js/register.js
// Registration form handler using jQuery AJAX (DEBUG VERSION)

$(document).ready(function () {
    const $form = $('#register-form');
    const $submitBtn = $('#submit-btn');
    const $btnText = $submitBtn.find('.btn-text');
    const $spinner = $submitBtn.find('.spinner-border');
    const $alertContainer = $('#alert-container');

    // Email validation regex
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Show alert message
    function showAlert(message, type = 'danger') {
        $alertContainer.html(`
            <div class="alert alert-${type} alert-custom alert-dismissible fade show" role="alert">
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        // Scroll to alert
        $alertContainer[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Clear alert
    function clearAlert() {
        $alertContainer.empty();
    }

    // Validate email format
    function isValidEmail(email) {
        return emailRegex.test(email);
    }

    // Validate password strength
    function isValidPassword(password) {
        return password.length >= 6;
    }

    // Set field as invalid
    function setInvalid($field, message) {
        $field.addClass('is-invalid');
        $field.siblings('.invalid-feedback').text(message);
    }

    // Clear field validation
    function clearValidation($field) {
        $field.removeClass('is-invalid');
    }

    // Clear all validations
    function clearAllValidations() {
        $('.form-control').removeClass('is-invalid');
    }

    // Validate form
    function validateForm() {
        let isValid = true;
        clearAllValidations();
        clearAlert();

        // Get form values
        const email = $('#email').val().trim();
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm-password').val();

        // Validate email
        if (!email) {
            setInvalid($('#email'), 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            setInvalid($('#email'), 'Please enter a valid email address');
            isValid = false;
        }

        // Validate username
        if (!username) {
            setInvalid($('#username'), 'Username is required');
            isValid = false;
        } else if (username.length < 3) {
            setInvalid($('#username'), 'Username must be at least 3 characters');
            isValid = false;
        }

        // Validate password
        if (!password) {
            setInvalid($('#password'), 'Password is required');
            isValid = false;
        } else if (!isValidPassword(password)) {
            setInvalid($('#password'), 'Password must be at least 6 characters');
            isValid = false;
        }

        // Validate confirm password
        if (!confirmPassword) {
            setInvalid($('#confirm-password'), 'Please confirm your password');
            isValid = false;
        } else if (password !== confirmPassword) {
            setInvalid($('#confirm-password'), 'Passwords do not match');
            isValid = false;
        }

        return isValid;
    }

    // Handle form submission
    $form.on('submit', function (e) {
        e.preventDefault();

        // Validate form
        if (!validateForm()) {
            return;
        }

        // Disable submit button
        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $spinner.removeClass('d-none');

        // Prepare data
        const formData = {
            email: $('#email').val().trim(),
            username: $('#username').val().trim(),
            password: $('#password').val()
        };

        // DEBUG: Log request data
        console.log('=== REGISTRATION REQUEST ===');
        console.log('URL:', 'assets/php/register.php');
        console.log('Data:', formData);

        // Send AJAX request
        $.ajax({
            url: 'assets/php/register.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (response) {
                console.log('=== SUCCESS RESPONSE ===');
                console.log(response);

                if (response.success) {
                    showAlert(response.message || 'Registration successful! Redirecting to login...', 'success');

                    // Reset form
                    $form[0].reset();

                    // Redirect to login after 2 seconds
                    setTimeout(function () {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showAlert(response.message || 'Registration failed. Please try again.', 'danger');

                    // Re-enable button
                    $submitBtn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $spinner.addClass('d-none');
                }
            },
            error: function (xhr, status, error) {
                console.log('=== ERROR RESPONSE ===');
                console.log('Status:', xhr.status);
                console.log('Status Text:', xhr.statusText);
                console.log('Response Text:', xhr.responseText);
                console.log('Error:', error);

                let errorMessage = 'An error occurred. Please try again.';

                // Try to parse JSON error
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    console.log('Parsed Error:', errorData);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    console.log('Could not parse error as JSON');
                    // If not JSON, show raw response
                    if (xhr.responseText) {
                        errorMessage = 'Server Error: ' + xhr.responseText.substring(0, 200);
                    }
                }

                if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection. Make sure XAMPP Apache is running.';
                } else if (xhr.status === 404) {
                    errorMessage = 'API endpoint not found. Check if register.php exists.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Check Apache error log.';
                }

                showAlert(errorMessage, 'danger');

                // Re-enable button
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $spinner.addClass('d-none');
            }
        });
    });

    // Real-time validation on input
    $('#email').on('blur', function () {
        const email = $(this).val().trim();
        if (email && !isValidEmail(email)) {
            setInvalid($(this), 'Please enter a valid email address');
        } else {
            clearValidation($(this));
        }
    });

    $('#username').on('blur', function () {
        const username = $(this).val().trim();
        if (username && username.length < 3) {
            setInvalid($(this), 'Username must be at least 3 characters');
        } else {
            clearValidation($(this));
        }
    });

    $('#password').on('blur', function () {
        const password = $(this).val();
        if (password && !isValidPassword(password)) {
            setInvalid($(this), 'Password must be at least 6 characters');
        } else {
            clearValidation($(this));
        }
    });

    $('#confirm-password').on('blur', function () {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        if (confirmPassword && password !== confirmPassword) {
            setInvalid($(this), 'Passwords do not match');
        } else {
            clearValidation($(this));
        }
    });

    // Clear validation on input
    $('.form-control').on('input', function () {
        clearValidation($(this));
    });

    // DEBUG: Test if jQuery and page are loaded
    console.log('Register.js loaded successfully');
    console.log('jQuery version:', $.fn.jquery);
});