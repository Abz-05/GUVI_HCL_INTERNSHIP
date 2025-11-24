// assets/js/profile.js
// Profile page handler using jQuery AJAX

$(document).ready(function () {
    const $form = $('#profile-form');
    const $submitBtn = $('#submit-btn');
    const $btnText = $submitBtn.find('.btn-text');
    const $spinner = $submitBtn.find('.spinner-border');
    const $alertContainer = $('#alert-container');
    const $logoutBtn = $('#logout-btn');

    // Check if user is logged in
    const sessionToken = localStorage.getItem('sessionToken');
    if (!sessionToken) {
        // Redirect to login if not logged in
        window.location.href = 'login.html';
        return;
    }

    // Show alert message
    function showAlert(message, type = 'success') {
        $alertContainer.html(`
            <div class="alert alert-${type} alert-custom alert-dismissible fade show" role="alert">
                <strong>${type === 'success' ? 'Success!' : type === 'info' ? 'Info!' : 'Error!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        // Auto dismiss after 5 seconds
        setTimeout(function () {
            $alertContainer.find('.alert').fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);
    }

    // Clear alert
    function clearAlert() {
        $alertContainer.empty();
    }

    // Load profile data
    function loadProfile() {
        $.ajax({
            url: 'assets/php/profile.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'get',
                token: sessionToken
            }),
            dataType: 'json',
            success: function (response) {
                if (response.success && response.user) {
                    const user = response.user;

                    // Populate form fields
                    $('#username').val(user.username || '');
                    $('#email').val(user.email || '');
                    $('#age').val(user.age || '');
                    $('#dob').val(user.dob || '');
                    $('#contact').val(user.contact || '');
                    $('#address').val(user.address || '');
                    $('#bio').val(user.bio || '');

                    // Update display name and email
                    $('#display-name').text(user.username || 'User');
                    $('#display-email').text(user.email || '');

                    // Update avatar initial
                    const initial = (user.username || 'U').charAt(0).toUpperCase();
                    $('#avatar-initial').text(initial);

                    // Update member since date
                    if (user.created_at) {
                        const date = new Date(user.created_at);
                        const options = { year: 'numeric', month: 'long', day: 'numeric' };
                        $('#member-since').text(date.toLocaleDateString('en-US', options));
                    }
                } else {
                    showAlert(response.message || 'Failed to load profile data.', 'danger');

                    // If session invalid, redirect to login
                    if (response.message && response.message.toLowerCase().includes('session')) {
                        setTimeout(function () {
                            localStorage.removeItem('sessionToken');
                            window.location.href = 'login.html';
                        }, 2000);
                    }
                }
            },
            error: function (xhr) {
                let errorMessage = 'Failed to load profile data.';

                if (xhr.status === 401) {
                    errorMessage = 'Session expired. Redirecting to login...';
                    localStorage.removeItem('sessionToken');
                    setTimeout(function () {
                        window.location.href = 'login.html';
                    }, 2000);
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
                }

                showAlert(errorMessage, 'danger');
            }
        });
    }

    // Update profile data
    function updateProfile() {
        // Validate age if provided
        const age = $('#age').val();
        if (age && (age < 1 || age > 120)) {
            showAlert('Please enter a valid age (1-120).', 'danger');
            return;
        }

        // Disable submit button
        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $spinner.removeClass('d-none');

        // Prepare data
        const formData = {
            action: 'update',
            token: sessionToken,
            age: $('#age').val() || null,
            dob: $('#dob').val() || null,
            contact: $('#contact').val().trim() || null,
            address: $('#address').val().trim() || null,
            bio: $('#bio').val().trim() || null
        };

        // Send AJAX request
        $.ajax({
            url: 'assets/php/profile.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showAlert(response.message || 'Profile updated successfully!', 'success');

                    // Reload profile data to ensure consistency
                    loadProfile();
                } else {
                    showAlert(response.message || 'Failed to update profile.', 'danger');
                }

                // Re-enable button
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $spinner.addClass('d-none');
            },
            error: function (xhr) {
                let errorMessage = 'Failed to update profile.';

                if (xhr.status === 401) {
                    errorMessage = 'Session expired. Redirecting to login...';
                    localStorage.removeItem('sessionToken');
                    setTimeout(function () {
                        window.location.href = 'login.html';
                    }, 2000);
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
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
    }

    // Handle logout
    function handleLogout() {
        $.ajax({
            url: 'assets/php/profile.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'logout',
                token: sessionToken
            }),
            dataType: 'json',
            complete: function () {
                // Clear local storage and redirect
                localStorage.removeItem('sessionToken');
                localStorage.removeItem('rememberUser');
                window.location.href = 'login.html';
            }
        });
    }

    // Event Handlers

    // Form submission
    $form.on('submit', function (e) {
        e.preventDefault();
        clearAlert();
        updateProfile();
    });

    // Logout button
    $logoutBtn.on('click', function (e) {
        e.preventDefault();

        if (confirm('Are you sure you want to logout?')) {
            handleLogout();
        }
    });

    // Age validation on input
    $('#age').on('input', function () {
        const age = $(this).val();
        if (age && (age < 1 || age > 120)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Phone number formatting (simple)
    $('#contact').on('input', function () {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        // Optional: Format as (XXX) XXX-XXXX
        // Leaving as simple for flexibility
        $(this).val(value);
    });

    // Initialize: Load profile data on page load
    loadProfile();
});