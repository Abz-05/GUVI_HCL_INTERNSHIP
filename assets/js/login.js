// assets/js/login.js
// Login form handler using jQuery AJAX

$(document).ready(function () {
    const $form = $('#login-form');
    const $submitBtn = $('#submit-btn');
    const $btnText = $submitBtn.find('.btn-text');
    const $spinner = $submitBtn.find('.spinner-border');
    const $alertContainer = $('#alert-container');
    const $rememberMe = $('#remember-me');

    // Check if user is already logged in
    const sessionToken = localStorage.getItem('sessionToken');
    if (sessionToken) {
        // Redirect to profile if already logged in
        window.location.href = 'profile.html';
        return;
    }

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
        const emailOrUsername = $('#emailOrUsername').val().trim();
        const password = $('#password').val();

        // Validate email/username
        if (!emailOrUsername) {
            setInvalid($('#emailOrUsername'), 'Email or username is required');
            isValid = false;
        }

        // Validate password
        if (!password) {
            setInvalid($('#password'), 'Password is required');
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
            emailOrUsername: $('#emailOrUsername').val().trim(),
            password: $('#password').val()
        };

        // Send AJAX request
        $.ajax({
            url: 'assets/php/login.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (response) {
                if (response.success && response.token) {
                    // Store session token in localStorage
                    localStorage.setItem('sessionToken', response.token);

                    // Store user data if remember me is checked
                    if ($rememberMe.is(':checked')) {
                        localStorage.setItem('rememberUser', $('#emailOrUsername').val().trim());
                    } else {
                        localStorage.removeItem('rememberUser');
                    }

                    showAlert('Login successful! Redirecting to profile...', 'success');

                    // Redirect to profile after 1.5 seconds
                    setTimeout(function () {
                        window.location.href = 'profile.html';
                    }, 1500);
                } else {
                    showAlert(response.message || 'Login failed. Please check your credentials.', 'danger');

                    // Re-enable button
                    $submitBtn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $spinner.addClass('d-none');
                }
            },
            error: function (xhr) {
                let errorMessage = 'An error occurred. Please try again.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
                } else if (xhr.status === 401) {
                    errorMessage = 'Invalid email/username or password.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }

                showAlert(errorMessage, 'danger');

                // Re-enable button
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $spinner.addClass('d-none');
            }
        });
    });

    // Load remembered user if exists
    const rememberedUser = localStorage.getItem('rememberUser');
    if (rememberedUser) {
        $('#emailOrUsername').val(rememberedUser);
        $rememberMe.prop('checked', true);
    }

    // Clear validation on input
    $('.form-control').on('input', function () {
        clearValidation($(this));
    });

    // Real-time validation on blur
    $('#emailOrUsername').on('blur', function () {
        const value = $(this).val().trim();
        if (!value) {
            setInvalid($(this), 'This field is required');
        } else {
            clearValidation($(this));
        }
    });

    $('#password').on('blur', function () {
        const value = $(this).val();
        if (!value) {
            setInvalid($(this), 'Password is required');
        } else {
            clearValidation($(this));
        }
    });
});