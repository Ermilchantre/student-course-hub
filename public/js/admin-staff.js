/* ==================================================
   STAFF FORM AND PHOTO UPLOAD JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages photo uploads and form validation for staff registration in the admin dashboard.
   Functionality: Handles real-time password validation, photo preview with removal, and form submission with error handling.
   UI Role: Provides a user-friendly interface for admins to register staff with visual feedback and validation.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

/* ==================================================
   STAFF PHOTO PREVIEW
   --------------------------------------------------
   Purpose: Displays a preview of the uploaded staff photo and allows removal.
   Functionality: Creates a preview container, shows the selected image, and adds a remove button.
   UI Role: Enhances user experience by allowing admins to preview and manage uploaded photos.
   Logic: Listens for file input changes, creates an image preview, adds a remove button, and clears the preview on removal.
*/
const photoInput = document.getElementById('photo');
if (photoInput) {
    // Creates a container for the photo preview
    const previewContainer = document.createElement('div');
    previewContainer.className = 'photo-preview';
    // Appends the preview container to the input’s parent
    photoInput.parentNode.appendChild(previewContainer);

    photoInput.addEventListener('change', function (e) {
        // Clears previous preview content
        previewContainer.innerHTML = '';
        const file = e.target.files[0];
        if (file) {
            // Creates an image element for the preview
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            // Revokes the object URL after the image loads to free memory
            img.onload = () => URL.revokeObjectURL(img.src);
            previewContainer.appendChild(img);
            // Adds a remove button to clear the preview
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-photo-btn';
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = () => {
                // Clears the file input and preview
                photoInput.value = '';
                previewContainer.innerHTML = '';
            };
            previewContainer.appendChild(removeBtn);
        }
    });
}

/* ==================================================
   ERROR MESSAGE DISPLAY
   --------------------------------------------------
   Purpose: Shows error messages for specific form fields.
   Functionality: Adds an error message div below the specified field and scrolls to it.
   UI Role: Provides clear feedback for form validation errors.
   Logic: Removes existing error messages, creates a new error div, styles the input, and focuses it.
*/
function showFieldError(field, message) {
    // Removes any existing error message for the field
    const old = document.querySelector('.error-message-' + field);
    if (old) old.remove();
    const input = document.getElementById(field);
    if (input) {
        // Creates a new error message div
        const div = document.createElement('div');
        div.className = 'error-message error-message-' + field;
        div.textContent = message;
        // Appends the error message to the input’s parent
        input.parentNode.appendChild(div);
        // Styles the input to indicate an error
        input.classList.add('error');
        // Scrolls to the field for user visibility
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Focuses the input for immediate correction
        input.focus();
    }
}

/* ==================================================
   CLEAR FIELD ERRORS
   --------------------------------------------------
   Purpose: Removes all error messages and error styles from form inputs.
   Functionality: Clears error message divs and removes error classes from inputs.
   UI Role: Resets the form’s visual state before new validations.
   Logic: Finds and removes all error messages and error classes from inputs.
*/
function clearFieldErrors() {
    // Removes all error message elements
    document.querySelectorAll('.error-message').forEach(e => e.remove());
    // Removes error class from all inputs
    document.querySelectorAll('.error').forEach(e => e.classList.remove('error'));
}

/* ==================================================
   SUCCESS MESSAGE DISPLAY
   --------------------------------------------------
   Purpose: Displays a success message after successful form submission.
   Functionality: Shows a temporary success message near the submit button and hides it after 6 seconds.
   UI Role: Provides positive feedback for successful staff registration.
   Logic: Creates or updates a success message div, displays it, and hides it after a timeout.
*/
function showSuccessMessage(message) {
    let container = document.querySelector('.staff-form-success-message');
    if (!container) {
        // Creates a success message container if it doesn’t exist
        container = document.createElement('div');
        container.className = 'staff-form-success-message';
        // Inserts the container after the submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && submitBtn.parentNode) {
            submitBtn.parentNode.insertBefore(container, submitBtn.nextSibling);
        } else {
            form.appendChild(container);
        }
    }
    // Sets and displays the success message
    container.textContent = message;
    container.style.display = 'block';
    // Hides the message after 6 seconds
    setTimeout(() => {
        container.style.display = 'none';
    }, 6000);
}

/* ==================================================
   PASSWORD VALIDATION AND FORM SUBMISSION
   --------------------------------------------------
   Purpose: Validates the password input and handles staff form submission.
   Functionality: Validates password length in real-time, submits form data via fetch, and handles server responses.
   UI Role: Ensures secure and user-friendly staff registration with immediate feedback.
   Logic: Listens for password input changes, validates length, submits form data, and displays success or error messages.
*/
const passwordInput = document.getElementById('password');
const form = document.getElementById('staff-form');
if (passwordInput && form) {
    passwordInput.addEventListener('input', function () {
        const password = this.value;
        const minLength = 6;
        const maxLength = 20;

        // Removes previous validation classes
        this.classList.remove('error', 'valid');

        // Validates password length
        if (password.length < minLength) {
            // Marks as error if too short
            this.classList.add('error');
            this.setCustomValidity(`Password must be at least ${minLength} characters long`);
        } else if (password.length > maxLength) {
            // Marks as error if too long
            this.classList.add('error');
            this.setCustomValidity(`Password must be no more than ${maxLength} characters long`);
        } else {
            // Marks as valid if within length constraints
            this.classList.add('valid');
            this.setCustomValidity('');
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            // Clears existing error messages
            clearFieldErrors();
            // Prepares form data for submission
            const formData = new FormData(form);
            // Sends POST request to server
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(async response => {
                    let result = {};
                    try {
                        // Parses JSON response
                        result = await response.json();
                    } catch (err) {
                        // Shows alert for unexpected response
                        alert('Unexpected server response');
                        return;
                    }
                    if (result.status === 'error' && result.field) {
                        // Shows field-specific error
                        showFieldError(result.field, result.message);
                    } else if (result.status === 'error') {
                        // Shows general error alert
                        alert(result.message || 'Error registering staff');
                    } else {
                        // Shows success message and resets form
                        showSuccessMessage(result.message || 'Staff registered successfully!');
                        form.reset();
                    }
                })
                .catch(() => {
                    // Shows alert for network or other errors
                    alert('Error registering staff');
                });
        });
    }
}