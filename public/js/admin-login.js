/* ==================================================
   ADMIN LOGIN AND PASSWORD RESET JAVASCRIPT
   --------------------------------------------------
   Purpose: Handles form validation, submission, and password visibility toggling for admin login and password reset forms.
   Functionality: Includes real-time input validation, async form submissions, and notification messages for user feedback.
   UI Role: Provides a secure and user-friendly interface for admin authentication and password management.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', function () {
    /* ==================================================
       PASSWORD VISIBILITY TOGGLE
       --------------------------------------------------
       Purpose: Toggles the visibility of the password input field in the login form.
       Functionality: Switches the password input type between 'password' and 'text' and updates the toggle icon.
       UI Role: Enhances usability by allowing users to view their password.
       Logic: Checks for the existence of toggle and password elements, then toggles the input type and updates the icon (eye/eye-slash) on click.
    */
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    if (togglePassword && passwordInput) {
        // Ensures elements exist before adding event listener
        togglePassword.addEventListener('click', function () {
            // Toggles input type between 'password' and 'text'
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            // Updates icon based on input type
            this.innerHTML = type === 'password' ? '<i class="uil uil-eye"></i>' : '<i class="uil uil-eye-slash"></i>';
        });
    }

    /* ==================================================
       MESSAGE DISPLAY FUNCTION
       --------------------------------------------------
       Purpose: Displays temporary notification messages for form feedback.
       Functionality: Shows a message with a specified type (e.g., success, error) in a designated div, with ARIA attributes for accessibility, and hides it after 5 seconds.
       UI Role: Provides clear visual feedback for form actions.
       Logic: Updates the message div with the provided text and type, applies ARIA attributes for screen readers, and uses a timeout to auto-hide the message.
    */
    function showMessage(message, type) {
        const messageDiv = document.getElementById('form-message');
        if (messageDiv) {
            // Sets the message content and type class (e.g., 'success' or 'error')
            messageDiv.textContent = message;
            messageDiv.className = `form-message ${type}`;
            // Makes the message visible
            messageDiv.style.display = 'block';
            // Adds ARIA attributes for accessibility
            messageDiv.setAttribute('role', 'alert');
            messageDiv.setAttribute('aria-live', 'polite');
            // Hides the message after 5 seconds for a clean UI
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }

    /* ==================================================
       ADMIN LOGIN FORM
       --------------------------------------------------
       Purpose: Manages validation and submission for the admin login form.
       Functionality: Validates email and password inputs in real-time, submits form data via async fetch, and handles success/error responses.
       UI Role: Ensures secure and user-friendly login experience with immediate feedback.
       Logic: Validates inputs on change, prevents form submission if invalid, sends data to the server, and redirects or shows errors based on the response.
    */
    const adminLoginForm = document.getElementById('admin-login-form');
    if (adminLoginForm) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitButton = adminLoginForm.querySelector('button[type="submit"]');

        /* Email Validation Function */
        function isValidEmail(email) {
            // Uses regex to check for valid email format (e.g., user@domain.com)
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        /* Password Validation Function */
        function isValidPassword(password) {
            // Ensures password is at least 6 characters long
            return password.length >= 6;
        }

        /* Real-Time Email Validation */
        if (emailInput) {
            emailInput.addEventListener('input', () => {
                const email = emailInput.value;
                // Checks if email is non-empty and invalid
                if (email && !isValidEmail(email)) {
                    // Sets error message and red border for invalid email
                    emailInput.setCustomValidity('Please enter a valid email address');
                    emailInput.style.borderColor = '#ff4444';
                } else {
                    // Clears error and resets border for valid or empty email
                    emailInput.setCustomValidity('');
                    emailInput.style.borderColor = '#e1e5e9';
                }
            });
        }

        /* Real-Time Password Validation */
        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                // Checks if password is non-empty and too short
                if (password && !isValidPassword(password)) {
                    // Sets error message and red border for invalid password
                    passwordInput.setCustomValidity('Password must be at least 6 characters');
                    passwordInput.style.borderColor = '#ff4444';
                } else {
                    // Clears error and resets border for valid or empty password
                    passwordInput.setCustomValidity('');
                    passwordInput.style.borderColor = '#e1e5e9';
                }
            });
        }

        /* Form Submission Handler */
        adminLoginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevents default form submission
            const email = emailInput.value.trim();
            const password = passwordInput.value;

            // Clears any previous error messages
            const messageDiv = document.getElementById('form-message');
            if (messageDiv) {
                messageDiv.style.display = 'none';
            }

            // Validates email presence
            if (!email) {
                showMessage('Please enter your email', 'error');
                emailInput.focus();
                return;
            }
            // Validates email format
            if (!isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                emailInput.focus();
                return;
            }

            // Validates password presence
            if (!password) {
                showMessage('Please enter your password', 'error');
                passwordInput.focus();
                return;
            }
            // Validates password length
            if (!isValidPassword(password)) {
                showMessage('Password must be at least 6 characters', 'error');
                passwordInput.focus();
                return;
            }

            // Disables submit button to prevent multiple submissions
            submitButton.disabled = true;
            // Stores original button text for restoration
            const originalText = submitButton.innerHTML;
            // Shows loading state with spinner icon
            submitButton.innerHTML = '<i class="uil uil-spinner-alt-3"></i> Signing in...';

            try {
                // Prepares form data for submission
                const formData = new FormData(adminLoginForm);
                // Sends async POST request to server
                const response = await fetch(adminLoginForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                // Checks if response is successful
                if (response.ok) {
                    if (result.success) {
                        // Shows success message and redirects to dashboard
                        showMessage('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = result.redirect || '/admin/dashboard';
                        }, 1000);
                    } else {
                        // Shows error message from server or default message
                        showMessage(result.message || 'Login failed. Please check your credentials.', 'error');
                    }
                } else {
                    // Handles non-OK HTTP responses
                    showMessage(result.message || 'Login failed. Please try again.', 'error');
                }
            } catch (error) {
                // Handles network or other errors
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                // Re-enables submit button and restores original text
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }

    /* ==================================================
       RESET PASSWORD FORM
       --------------------------------------------------
       Purpose: Manages validation and submission for the password reset request form.
       Functionality: Validates email input and submits a reset request via async fetch, handling success/error responses.
       UI Role: Allows users to request a password reset link with clear feedback.
       Logic: Validates email input, sends a reset request to the server, and displays success or error messages based on the response.
    */
    const resetPasswordForm = document.getElementById('reset-password-form');
    if (resetPasswordForm) {
        const emailInput = document.getElementById('email');
        const submitButton = resetPasswordForm.querySelector('button[type="submit"]');

        /* Email Validation Function */
        function isValidEmail(email) {
            // Uses regex to check for valid email format
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        /* Form Submission Handler */
        resetPasswordForm.addEventListener('submit', async (e) => {
            console.log('Tentando enviar o formulário de reset de senha');
            e.preventDefault(); // Prevents default form submission
            const email = emailInput.value.trim();
            const messageDiv = document.getElementById('form-message');
            messageDiv.style.display = 'none'; // Clears previous messages

            // Validates email presence
            if (!email) {
                showMessage('Please enter your email', 'error');
                emailInput.focus();
                return;
            }
            // Validates email format
            if (!isValidEmail(email)) {
                showMessage('Please enter a valid email address', 'error');
                emailInput.focus();
                return;
            }

            // Disables submit button to prevent multiple submissions
            submitButton.disabled = true;
            // Stores original button text
            const originalText = submitButton.innerHTML;
            // Shows loading state with spinner
            submitButton.innerHTML = '<i class="uil uil-spinner-alt-3"></i> Sending...';

            try {
                // Prepares form data for submission
                const formData = new FormData(resetPasswordForm);
                // Sends async POST request to server
                const response = await fetch(resetPasswordForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                let result = {};
                try {
                    // Attempts to parse JSON response
                    result = await response.json();
                } catch (e) {
                    // Fallback to text response if JSON parsing fails
                    result = { message: await response.text() };
                }
                // Checks if response is successful
                if (response.ok && (result.success === true || result.message)) {
                    // Shows success message or default reset link message
                    showMessage(result.message || 'If the email exists, a reset link will be sent.', 'success');
                } else {
                    // Shows error message from server or default message
                    showMessage(result.message || 'Failed to send reset link.', 'error');
                }
            } catch (error) {
                // Handles network or other errors
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                // Re-enables submit button and restores original text
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }

    /* ==================================================
       MULTIPLE PASSWORD VISIBILITY TOGGLE
       --------------------------------------------------
       Purpose: Toggles visibility for multiple password fields.
       Functionality: Switches the input type for targeted password fields and updates the toggle icon.
       UI Role: Enhances usability for forms with multiple password inputs.
       Logic: Iterates over toggle buttons, targets corresponding inputs via data attributes, and toggles their type and icon.
    */
    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            // Gets the target input ID from data attribute
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                // Toggles input type between 'password' and 'text'
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                // Updates icon based on input type
                this.innerHTML = type === 'password' ? '<i class="uil uil-eye"></i>' : '<i class="uil uil-eye-slash"></i>';
            }
        });
    });

    /* ==================================================
       SET NEW PASSWORD FORM
       --------------------------------------------------
       Purpose: Manages validation and submission for setting a new password.
       Functionality: Validates password and confirmation inputs, submits via async fetch, and redirects on success.
       UI Role: Allows users to securely set a new password with clear feedback.
       Logic: Checks for matching passwords, sends data to the server, displays success/error messages, and redirects to login on success.
    */
    const setPasswordForm = document.getElementById('set-password-form');
    if (setPasswordForm) {
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirm');
        const submitButton = setPasswordForm.querySelector('button[type="submit"]');
        const successMessageDiv = document.getElementById('success-message');

        /* Form Submission Handler */
        setPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevents default form submission
            const password = passwordInput.value;
            const passwordConfirm = passwordConfirmInput.value;
            const messageDiv = document.getElementById('form-message');
            messageDiv.style.display = 'none'; // Clears error messages
            successMessageDiv.style.display = 'none'; // Clears success messages

            // Validates password presence
            if (!password || !passwordConfirm) {
                showMessage('Please enter and confirm your new password', 'error');
                return;
            }
            // Validates password match
            if (password !== passwordConfirm) {
                showMessage('Passwords do not match', 'error');
                return;
            }

            // Disables submit button to prevent multiple submissions
            submitButton.disabled = true;
            // Stores original button text
            const originalText = submitButton.innerHTML;
            // Shows loading state with spinner
            submitButton.innerHTML = '<i class="uil uil-spinner-alt-3"></i> Saving...';

            try {
                // Prepares form data for submission
                const formData = new FormData(setPasswordForm);
                // Sends async POST request to server
                const response = await fetch(setPasswordForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                let result = {};
                try {
                    // Attempts to parse JSON response
                    result = await response.json();
                } catch (e) {
                    // Fallback to text response if JSON parsing fails
                    result = { message: await response.text() };
                }
                // Checks if response is successful
                if (response.ok && (result.success === true || result.message)) {
                    // Shows success message and redirects to login
                    successMessageDiv.textContent = result.message || 'Password reset successfully! You can now log in.';
                    successMessageDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = setPasswordForm.getAttribute('action').replace('/reset-password', '/login');
                    }, 2000);
                } else {
                    // Shows error message from server or default message
                    showMessage(result.message || 'Failed to reset password.', 'error');
                }
            } catch (error) {
                // Handles network or other errors
                showMessage('An error occurred. Please try again.', 'error');
            } finally {
                // Re-enables submit button and restores original text
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }
});