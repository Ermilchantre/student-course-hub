/* ==================================================
   PROGRAMME MANAGEMENT JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages the creation, editing, deletion, and display of programmes in the admin dashboard.
   Functionality: Handles form submissions, fetches programme data, updates UI with search and filter functionality, and manages pagination and statistics.
   UI Role: Provides an intuitive interface for admins to manage programmes with real-time feedback and dynamic updates.
   Version: 2.0 - URLs corrected to /admin/api/programmes
   Last Updated: June 28, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', () => {
    /* ==================================================
       INITIALIZATION
       --------------------------------------------------
       Purpose: Sets up programme management functionality on page load.
       Functionality: Initializes event listeners, loads programmes, and prepares the UI for interaction.
       UI Role: Ensures the programme management interface is ready for use.
       Logic: Defines a variable to track editing state and loads programmes immediately.
    */
    let currentEditId = null; // Tracks the ID of the programme being edited

    /* ==================================================
       MESSAGE DISPLAY FUNCTION
       --------------------------------------------------
       Purpose: Displays temporary notification messages for programme operations.
       Functionality: Updates a message div with the provided text and type, shows it, and hides it after 5 seconds.
       UI Role: Provides user feedback for actions like saving or deleting programmes.
       Logic: Sets the message content and type, displays the message, and uses a timeout to hide it automatically.
    */
    function showMessage(message, type) {
        const messageDiv = document.getElementById('form-message');
        // Updates message content and applies type class (e.g., 'success' or 'error')
        messageDiv.textContent = message;
        messageDiv.className = `form-message ${type}`;
        // Makes the message visible
        messageDiv.style.display = 'block';
        // Hides the message after 5 seconds for a clean UI
        setTimeout(() => messageDiv.style.display = 'none', 5000);
    }

    /* ==================================================
       UPDATE STATISTICS
       --------------------------------------------------
       Purpose: Updates programme statistics displayed in the UI.
       Functionality: Calculates total and published programme counts and updates corresponding elements.
       UI Role: Shows key metrics about programmes for quick reference.
       Logic: Counts total programmes and published programmes, then updates text in stat elements.
    */
    function updateStats(programmes) {
        // Calculates total and published programme counts
        const totalProgrammes = programmes.length;
        const publishedProgrammes = programmes.filter(p => p.is_published == 1).length;
        // Updates stat display elements
        document.getElementById('total-programmes').textContent = `${totalProgrammes} Programmes`;
        document.getElementById('published-programmes').textContent = `${publishedProgrammes} Published`;
    }

    /* ==================================================
       FILTER PROGRAMMES
       --------------------------------------------------
       Purpose: Filters programme table rows based on search and select inputs.
       Functionality: Hides rows that don’t match search term, level, or status filters.
       UI Role: Enables admins to quickly find specific programmes.
       Logic: Compares row data against search term and filter values, toggling visibility accordingly.
    */
    function filterProgrammes() {
        // Gets filter values
        const searchTerm = document.getElementById('programme-search').value.toLowerCase();
        const filterLevel = document.getElementById('filter-level').value;
        const filterStatus = document.getElementById('filter-status').value;
        
        // Iterates through table rows to apply filters
        document.querySelectorAll('.programmes-table tbody tr').forEach(row => {
            const titleElement = row.querySelector('.programme-info h4');
            const levelElement = row.querySelector('.level-badge');
            const statusElement = row.querySelector('.status-badge');
            
            if (!titleElement || !levelElement || !statusElement) {
                return;
            }
            
            const title = titleElement.textContent.toLowerCase();
            const level = levelElement.textContent.trim();
            const status = statusElement.textContent.trim();
            
            // Checks if row matches all filter criteria
            const matchesSearch = searchTerm === '' || title.includes(searchTerm);
            const matchesLevel = filterLevel === '' || level === filterLevel;
            const matchesStatus = filterStatus === '' ||
                (filterStatus === 'published' && status === 'Published') ||
                (filterStatus === 'draft' && status === 'Draft');
            
            // Shows or hides row based on filter matches
            const shouldShow = matchesSearch && matchesLevel && matchesStatus;
            row.style.display = shouldShow ? '' : 'none';
        });
    }

    /* ==================================================
       EDIT PROGRAMME FUNCTION
       --------------------------------------------------
       Purpose: Populates the programme form for editing an existing programme.
       Functionality: Fetches programme data by ID, fills form fields, and updates UI for editing mode.
       UI Role: Allows admins to edit programme details seamlessly.
       Logic: Retrieves programme data, populates form fields, handles image preview, and scrolls to the form.
    */
    window.editProgramme = function (id) {
        const url = `/admin/api/programmes/${id}`;
        // Fetches programme data from the server
        fetch(url)
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success' && result.data) {
                    const programme = result.data;
                    // Populates form fields with programme data
                    document.getElementById('title').value = programme.title;
                    document.getElementById('level').value = programme.level;
                    document.getElementById('duration_years').value = programme.duration_years || '';
                    document.getElementById('credits').value = programme.credits || '';
                    document.getElementById('description').value = programme.description;
                    document.getElementById('entry_requirements').value = programme.entry_requirements || '';
                    document.getElementById('career_prospects').value = programme.career_prospects || '';
                    // Updates image preview if an image exists
                    if (programme.image_url) {
                        const imagePreview = document.getElementById('image-preview');
                        if (imagePreview) {
                            imagePreview.src = programme.image_url;
                            imagePreview.style.display = 'block';
                        }
                    }
                    // Sets published status checkbox
                    const isPublished = programme.is_published == 1 || programme.is_published === '1';
                    document.getElementById('is_published').checked = isPublished;
                    // Updates UI for editing mode
                    document.getElementById('form-title').textContent = 'Edit Programme';
                    document.getElementById('submit-text').textContent = 'Update Programme';
                    document.getElementById('cancel-btn').style.display = 'inline-flex';
                    // Stores programme ID for editing
                    currentEditId = id;
                    // Scrolls to form with slight delay for DOM rendering
                    setTimeout(() => {
                        document.querySelector('.programme-form-section').scrollIntoView({ behavior: 'smooth' });
                    }, 100);
                } else {
                    // Shows error if programme loading fails
                    showMessage('Error loading programme', 'error');
                }
            })
            .catch(() => showMessage('Error loading programme', 'error'));
    };

    /* ==================================================
       CANCEL EDIT FUNCTION
       --------------------------------------------------
       Purpose: Resets the programme form to create mode.
       Functionality: Clears form fields, resets UI elements, and hides image preview.
       UI Role: Allows admins to abandon editing and start a new programme.
       Logic: Resets form, updates title and button text, clears editing ID, and hides image preview.
    */
    window.cancelEdit = function () {
        // Resets the form to its initial state
        document.getElementById('programme-form').reset();
        // Updates form title and button text for creation mode
        document.getElementById('form-title').textContent = 'Create New Programme';
        document.getElementById('submit-text').textContent = 'Create Programme';
        document.getElementById('cancel-btn').style.display = 'none';
        // Resets published checkbox
        document.getElementById('is_published').checked = false;
        // Clears image preview
        const imagePreview = document.getElementById('image-preview');
        if (imagePreview) {
            imagePreview.src = '#';
            imagePreview.style.display = 'none';
        }
        // Clears editing ID
        currentEditId = null;
    };

    /* ==================================================
       DELETE PROGRAMME FUNCTION
       --------------------------------------------------
       Purpose: Deletes a programme from the database and updates the UI.
       Functionality: Sends a DELETE request, removes the programme row with animation, and reloads programmes.
       UI Role: Allows admins to remove programmes with confirmation and feedback.
       Logic: Confirms deletion, sends DELETE request, animates row removal, and refreshes the programme list.
    */
    window.deleteProgramme = function (id, title) {
        // Prompts user for confirmation
        if (confirm(`Are you sure you want to delete the programme "${title}" ? `)) {
            const url = `/admin/api/programmes/${id}`;
            // Sends DELETE request to server
            fetch(url, { method: 'DELETE' })
                .then((response) => {
                    // Fades out the programme row
                    const row = document.querySelector(`tr[data-programme-id="${id}"]`);
                    row.style.opacity = '0';
                    // Removes row and updates UI after animation
                    setTimeout(() => {
                        row.remove();
                        showMessage('Programme deleted successfully!', 'success');
                        loadProgrammes(); // Reloads to update statistics
                    }, 300);
                })
                .catch(() => showMessage('Error deleting programme', 'error'));
        }
    };

    /* ==================================================
       PROGRAMME FORM SUBMISSION
       --------------------------------------------------
       Purpose: Handles creation and updating of programmes via form submission.
       Functionality: Validates form data, sends POST request with FormData, and updates UI on success.
       UI Role: Enables admins to create or update programmes with validation and feedback.
       Logic: Validates required fields, constructs FormData, sends request, and resets form on success.
    */
    const form = document.getElementById('programme-form');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        // Validates required fields
        const title = document.getElementById('title').value.trim();
        const level = document.getElementById('level').value;
        const duration = document.getElementById('duration_years').value;
        const description = document.getElementById('description').value.trim();
        if (!title || !level || !duration || !description) {
            showMessage('Please fill in all required fields', 'error');
            return;
        }
        // Prepares form data
        const formData = new FormData(form);
        const isPublishedCheckbox = document.getElementById('is_published');
        // Sets published status in form data
        formData.set('is_published', isPublishedCheckbox && isPublishedCheckbox.checked ? '1' : '0');
        // Uses POST for both create and update to support file uploads
        const method = 'POST';
        const url = currentEditId ? `/admin/api/programmes/${currentEditId}` : `/admin/api/programmes`;
        // Sends request to server
        fetch(url, {
            method,
            body: formData // Browser handles Content-Type for FormData
        })
            .then(async (response) => {
                let result = {};
                try {
                    // Parses JSON response
                    result = await response.json();
                } catch (e) {
                    // Handles unexpected server response
                    showMessage('Unexpected server response', 'error');
                    return;
                }
                if (response.ok && result.status === 'success') {
                    // Shows success message based on create or update
                    showMessage(
                        currentEditId ? 'Programme updated successfully!' : 'Programme created successfully!',
                        'success'
                    );
                    // Resets form and reloads programmes
                    cancelEdit();
                    loadProgrammes();
                } else {
                    // Shows error message if saving fails
                    showMessage(result.message || 'Error saving programme', 'error');
                }
            })
            .catch(() => showMessage('Error saving programme', 'error'));
    });

    /* ==================================================
       FILTER EVENT LISTENERS
       --------------------------------------------------
       Purpose: Attaches event listeners for search and filter inputs.
       Functionality: Triggers programme filtering on input changes.
       UI Role: Enables real-time filtering of the programme table.
       Logic: Listens for changes in search, level, and status inputs and calls filterProgrammes.
    */
    document.getElementById('programme-search').addEventListener('input', filterProgrammes);
    document.getElementById('filter-level').addEventListener('change', filterProgrammes);
    document.getElementById('filter-status').addEventListener('change', filterProgrammes);
    document.getElementById('cancel-btn').addEventListener('click', cancelEdit);

    /* ==================================================
       IMAGE UPLOAD PREVIEW
       --------------------------------------------------
       Purpose: Previews and manages image uploads for programmes.
       Functionality: Displays a preview of selected images and allows removal.
       UI Role: Enhances user experience by showing image previews before submission.
       Logic: Listens for file input changes, updates preview, and handles image removal.
    */
    const imageInput = document.getElementById('image_url');
    const imagePreview = document.getElementById('image-preview');
    const photoPreviewDiv = document.querySelector('.photo-preview');
    const removeBtn = document.getElementById('remove-image-btn');
    if (imageInput && imagePreview && removeBtn && photoPreviewDiv) {
        // Hides remove button by default
        removeBtn.style.display = 'none';
        imageInput.addEventListener('change', function (event) {
            // Gets selected file
            const [file] = event.target.files;
            if (file) {
                // Shows preview of selected image
                imagePreview.src = URL.createObjectURL(file);
                photoPreviewDiv.style.display = 'flex';
                removeBtn.style.display = 'inline-flex';
            } else {
                // Hides preview if no file is selected
                photoPreviewDiv.style.display = 'none';
                removeBtn.style.display = 'none';
            }
        });
        removeBtn.addEventListener('click', function () {
            // Clears image input and preview
            imageInput.value = '';
            imagePreview.src = '#';
            photoPreviewDiv.style.display = 'none';
            removeBtn.style.display = 'none';
        });
    }

    /* ==================================================
       RENDER PAGINATION
       --------------------------------------------------
       Purpose: Generates pagination controls for the programme table.
       Functionality: Creates numbered buttons for navigating pages, highlighting the current page.
       UI Role: Enables navigation through paginated programme lists.
       Logic: Calculates total pages, creates buttons, and hides pagination if only one page exists.
    */
    function renderPagination(total, currentPage, pageSize = 4) {
        const container = document.querySelector('.programmes-table-container');
        let pagination = container.querySelector('.pagination');
        // Creates pagination container if it doesn’t exist
        if (!pagination) {
            pagination = document.createElement('div');
            pagination.className = 'pagination';
            container.appendChild(pagination);
        }
        // Clears existing pagination buttons
        pagination.innerHTML = '';
        // Calculates total pages based on programme count
        const totalPages = Math.ceil(total / pageSize);
        // Creates a button for each page
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'btn' + (i === currentPage ? ' active' : '');
            btn.onclick = () => loadProgrammes(i);
            pagination.appendChild(btn);
        }
        // Shows pagination only if multiple pages exist
        pagination.style.display = totalPages > 1 ? 'flex' : 'none';
    }

    /* ==================================================
       LOAD PROGRAMMES
       --------------------------------------------------
       Purpose: Fetches and displays paginated programme data in a table.
       Functionality: Retrieves programmes, renders table rows, and updates pagination and statistics.
       UI Role: Displays a list of programmes with details and action buttons.
       Logic: Fetches programme data, slices for pagination, creates table rows, and updates UI components.
    */
    function loadProgrammes(page = 1) {
        // Adds timestamp to prevent caching
        const url = '/admin/api/programmes?_=' + new Date().getTime();
        const pageSize = 4;
        // Fetches programme data from server
        fetch(url)
            .then(res => res.json())
            .then(result => {
                // Ensures programmes array exists
                const programmes = result.data || [];
                const tbody = document.querySelector('.programmes-table tbody');
                // Clears existing table content
                tbody.innerHTML = '';
                // Calculates pagination range
                const start = (page - 1) * pageSize;
                const end = start + pageSize;
                const paginated = programmes.slice(start, end);
                // Creates a table row for each programme
                paginated.forEach(programme => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-programme-id', programme.id);
                    // Truncates description for display
                    const shortDescription = programme.description.length > 100
                        ? programme.description.substring(0, 100) + '...'
                        : programme.description;
                    row.innerHTML = `
                        <td>
                            <div class="programme-info">
                                <h4>${programme.title}</h4>
                                <p>${shortDescription}</p>
                            </div>
                        </td>
                        <td><span class="level-badge level-${programme.level.toLowerCase()}">${programme.level}</span></td>
                        <td><span class="duration-badge">${programme.duration_years || 3} years</span></td>
                        <td><span class="credits-badge">${programme.credits || 'N/A'}</span></td>
                        <td><span class="status-badge status-${programme.is_published == 1 ? 'published' : 'draft'}">${programme.is_published == 1 ? 'Published' : 'Draft'}</span></td>
                        <td class="actions-cell">
                            <button type="button" class="btn btn-edit" onclick="editProgramme(${programme.id})" aria-label="Edit programme ${programme.title}">
                                <i class="uil uil-edit"></i>
                            </button>
                            <button type="button" class="btn btn-delete" onclick="deleteProgramme(${programme.id}, '${programme.title}')" aria-label="Delete programme ${programme.title}">
                                <i class="uil uil-trash-alt"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                // Updates pagination and statistics
                renderPagination(programmes.length, page, pageSize);
                updateStats(programmes);
            })
            .catch(() => showMessage('Error loading programmes', 'error'));
    }

    /* ==================================================
       INITIAL PROGRAMME LOAD
       --------------------------------------------------
       Purpose: Loads programmes when the page is initialized.
       Functionality: Calls loadProgrammes to populate the table on startup.
       UI Role: Ensures the programme table is populated immediately.
       Logic: Triggers the loadProgrammes function to fetch and display data.
    */
    loadProgrammes();
});