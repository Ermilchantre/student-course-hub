/* ==================================================
   INTEREST FORM JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages form submission, validation, and PIN modal display for the interest form.
   Functionality: Auto-populates study levels, validates form fields, submits data, and displays a PIN modal on success.
   UI Role: Provides a user-friendly interface for registering interest with real-time validation and feedback.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', function () {
    /* ==================================================
       FORM SETUP AND AUTO-POPULATION
       --------------------------------------------------
       Purpose: Initializes form elements and auto-populates study level based on programme selection.
       Functionality: Maps programme IDs to study levels and updates the level select field.
       UI Role: Simplifies form input by automatically setting the study level.
       Logic: Listens for programme select changes and sets the level based on a predefined mapping.
    */
    const form = document.getElementById('interest-form');
    const programSelect = document.getElementById('program');
    const levelSelect = document.getElementById('level');

    // Auto-populate study level based on programme selection
    programSelect.addEventListener('change', function () {
        const selectedProgram = this.value;
        // Defines programme-to-level mapping
        const programmesData = {
            1: 'undergraduate', // BSc Computer Science
            2: 'postgraduate',  // MSc Data Science
            3: 'undergraduate', // BA Business Management
            4: 'undergraduate', // BSc Physics
            5: 'postgraduate',  // MA Literature
            6: 'postgraduate'   // MSc Cybersecurity
        };

        // Updates level select if a valid programme is selected
        if (levelSelect && programmesData[selectedProgram]) {
            levelSelect.value = programmesData[selectedProgram];
        }
    });

    /* ==================================================
       FORM SUBMISSION
       --------------------------------------------------
       Purpose: Handles form submission with validation and server communication.
       Functionality: Validates form, sends data via POST, and shows a PIN modal or error message.
       UI Role: Ensures valid form submission and provides user feedback.
       Logic: Prevents default submission, validates fields, sends form data, and handles responses.
    */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        // Clears existing messages
        removeMessages();
        // Validates form fields
        const isValid = validateForm();
        if (!isValid) {
            return;
        }
        // Prepares form data for submission
        const formData = new FormData(form);
        // Sends POST request to server
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                // Handles successful response with PIN
                if (result.success && result.pin) {
                    showPinModal(result.pin);
                    form.reset();
                } else if (result.error) {
                    // Shows error message from server
                    showMessage(result.error, 'error');
                } else {
                    // Shows generic error for unexpected response
                    showMessage('An unexpected error occurred.', 'error');
                }
            })
            .catch(() => {
                // Shows error for network issues
                showMessage('Error submitting the form. Please try again.', 'error');
            });
    });

    /* ==================================================
       REAL-TIME VALIDATION
       --------------------------------------------------
       Purpose: Validates form fields in real-time.
       Functionality: Checks fields on blur and input to provide immediate feedback.
       UI Role: Enhances user experience with instant validation feedback.
       Logic: Listens for blur and input events to validate fields and display errors.
    */
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function () {
            // Validates field on blur
            validateField(this);
        });
        input.addEventListener('input', function () {
            // Re-validates field on input if it has an error
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
});

/* ==================================================
   FORM VALIDATION
   --------------------------------------------------
   Purpose: Validates all required form fields.
   Functionality: Checks required fields, email, and phone number formats.
   UI Role: Ensures valid data before submission with user feedback.
   Logic: Iterates through required fields, validates each, and checks email/phone formats.
*/
function validateForm() {
    const form = document.getElementById('interest-form');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    // Validates all required fields
    requiredFields.forEach(field => {
        const fieldValid = validateField(field);
        if (!fieldValid) {
            isValid = false;
        }
    });

    // Validates email format
    const emailField = document.getElementById('email');
    if (emailField.value && !isValidEmail(emailField.value)) {
        showFieldError(emailField, 'Please enter a valid email address');
        isValid = false;
    }

    // Validates phone number format if provided
    const phoneField = document.getElementById('phone');
    if (phoneField.value && !isValidPhone(phoneField.value)) {
        showFieldError(phoneField, 'Please enter a valid phone number');
        isValid = false;
    }

    return isValid;
}

/* ==================================================
   FIELD VALIDATION
   --------------------------------------------------
   Purpose: Validates individual form fields.
   Functionality: Checks for empty required fields and specific formats (email, phone).
   UI Role: Provides immediate error feedback for individual fields.
   Logic: Removes existing errors, checks for required values, and validates formats.
*/
function validateField(field) {
    const value = field.value.trim();
    // Removes existing error messages
    removeFieldError(field);

    // Checks for empty required fields
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }

    // Validates email format
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    }

    // Validates phone number format
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        showFieldError(field, 'Please enter a valid phone number');
        return false;
    }

    return true;
}

/* ==================================================
   SHOW FIELD ERROR
   --------------------------------------------------
   Purpose: Displays error messages for form fields.
   Functionality: Adds an error class and message below the field.
   UI Role: Highlights invalid fields with clear error messages.
   Logic: Adds error class, removes existing errors, and appends a new error message.
*/
function showFieldError(field, message) {
    // Marks field as invalid
    field.classList.add('error');
    // Removes existing error messages
    removeFieldError(field);
    // Creates and styles error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '0.25rem';
    // Appends error message to field’s parent
    field.parentNode.appendChild(errorDiv);
}

/* ==================================================
   REMOVE FIELD ERROR
   --------------------------------------------------
   Purpose: Clears error messages and styles from a field.
   Functionality: Removes error class and associated error messages.
   UI Role: Resets field appearance after correction.
   Logic: Removes error class and all error message elements.
*/
function removeFieldError(field) {
    // Clears error class
    field.classList.remove('error');
    // Removes all error messages for the field
    const errors = field.parentNode.querySelectorAll('.field-error');
    errors.forEach(errorDiv => errorDiv.remove());
}

/* ==================================================
   SHOW MESSAGE
   --------------------------------------------------
   Purpose: Displays success or error messages for form submission.
   Functionality: Appends a message to the form and removes it after 5 seconds.
   UI Role: Provides user feedback for form submission outcomes.
   Logic: Creates a styled message div and auto-removes it after a timeout.
*/
function showMessage(message, type) {
    const form = document.getElementById('interest-form');
    // Creates message div with appropriate class
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.textContent = message;
    // Appends message to form
    form.appendChild(messageDiv);
    // Removes message after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

/* ==================================================
   REMOVE MESSAGES
   --------------------------------------------------
   Purpose: Clears all success and error messages from the form.
   Functionality: Removes all message elements before new validations.
   UI Role: Ensures a clean UI by clearing outdated messages.
   Logic: Selects and removes all message divs.
*/
function removeMessages() {
    const messages = document.querySelectorAll('.success-message, .error-message');
    messages.forEach(message => message.remove());
}

/* ==================================================
   EMAIL VALIDATION
   --------------------------------------------------
   Purpose: Validates email format.
   Functionality: Checks if an email matches a standard regex pattern.
   UI Role: Ensures valid email input for form submission.
   Logic: Uses a regex to test for valid email structure.
*/
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/* ==================================================
   PHONE VALIDATION
   --------------------------------------------------
   Purpose: Validates phone number format.
   Functionality: Checks if a phone number matches an acceptable pattern.
   UI Role: Ensures valid phone input for form submission.
   Logic: Normalizes phone number and tests against a regex.
*/
function isValidPhone(phone) {
    // Allows various phone formats, removes spaces, dashes, and parentheses
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

/* ==================================================
   FORM GROUP VISUAL FEEDBACK
   --------------------------------------------------
   Purpose: Adds visual effects to form groups on interaction.
   Functionality: Applies transform animations on focus/blur events.
   UI Role: Enhances form interactivity with subtle animations.
   Logic: Listens for focus/blur events and applies transform styles.
*/
document.addEventListener('DOMContentLoaded', function () {
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach(group => {
        const input = group.querySelector('input, select, textarea');
        if (input) {
            input.addEventListener('focus', function () {
                // Lifts form group on focus
                group.style.transform = 'translateY(-2px)';
                group.style.transition = 'transform 0.2s ease';
            });
            input.addEventListener('blur', function () {
                // Resets form group position
                group.style.transform = 'translateY(0)';
            });
        }
    });
});

/* ==================================================
   SHOW PIN MODAL
   --------------------------------------------------
   Purpose: Displays a modal with a PIN after successful form submission.
   Functionality: Creates and shows a modal with a copyable PIN and close button.
   UI Role: Provides confirmation and access to the PIN for the user.
   Logic: Removes existing modal, creates new modal HTML, and appends it to the body.
*/
function showPinModal(pin) {
    // Removes any existing modal
    closePinModal();
    // Creates modal HTML with PIN and buttons
    const modalHtml = `
        <div class="pin-modal-overlay">
            <div class="pin-modal-content">
                <h2 style="color:#1976d2;"><i class="uil uil-check-circle"></i> Interest Registered!</h2>
                <p style="margin-bottom:0.7rem;">Your Access PIN:</p>
                <div class="pin-value" style="font-size:2rem; font-weight:bold; color:#1976d2; margin:1rem 0;">${pin}</div>
                <button class="btn btn-info" onclick="copyPinValue('${pin}')"><i class="uil uil-copy"></i> Copy PIN</button>
                <div id="copy-feedback-modal" style="color:#388e3c; margin-top:0.5rem; display:none; font-size:1rem; font-weight:500;">PIN copied!</div>
                <button class="btn btn-primary" style="margin-top:1.2rem;" onclick="closePinModal()">Close</button>
            </div>
        </div>
    `;
    // Appends modal to the body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

/* ==================================================
   CLOSE PIN MODAL
   --------------------------------------------------
   Purpose: Closes the PIN modal.
   Functionality: Removes the modal overlay from the DOM.
   UI Role: Allows users to dismiss the PIN modal.
   Logic: Finds and removes the modal overlay element.
*/
function closePinModal() {
    const overlay = document.querySelector('.pin-modal-overlay');
    if (overlay) overlay.remove();
}

/* ==================================================
   COPY PIN VALUE
   --------------------------------------------------
   Purpose: Copies the PIN to the clipboard and shows feedback.
   Functionality: Uses the clipboard API to copy the PIN and displays a temporary confirmation.
   UI Role: Simplifies PIN copying with visual feedback.
   Logic: Copies PIN, shows feedback, and hides it after 2 seconds.
*/
window.copyPinValue = function (pin) {
    // Copies PIN to clipboard
    navigator.clipboard.writeText(pin).then(function () {
        const feedback = document.getElementById('copy-feedback-modal');
        if (feedback) {
            // Shows copy confirmation
            feedback.style.display = 'block';
            // Hides feedback after 2 seconds
            setTimeout(function () {
                feedback.style.display = 'none';
            }, 2000);
        }
    });
};

/* ==================================================
   MODAL STYLING
   --------------------------------------------------
   Purpose: Defines CSS styles for the PIN modal.
   Functionality: Adds styles for modal overlay, content, and responsive design.
   UI Role: Ensures a visually appealing and responsive modal.
   Logic: Creates a style element and appends it to the document head.
*/
const style = document.createElement('style');
style.innerHTML = `
    .pin-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;
    }
    .pin-modal-content {
        background: #fff; border-radius: 10px; padding: 2.2rem 2rem 1.5rem 2rem; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.15);
        min-width: 320px; max-width: 95vw;
    }
    .pin-value {
        font-size: 2rem; font-weight: bold; color: #1976d2; margin: 1rem 0;
    }
    @media(max-width: 600px) {
        .pin-modal-content { padding: 1.2rem 0.5rem 1rem 0.5rem; min-width: 0; }
        .pin-value { font-size: 1.3rem; }
    }
`;
document.head.appendChild(style);