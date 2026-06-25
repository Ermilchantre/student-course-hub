/* ==================================================
   MODULE MANAGEMENT JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages the creation, editing, deletion, and display of modules in the admin dashboard.
   Functionality: Handles form submissions, fetches module data, updates UI with search and filter functionality, and manages pagination.
   UI Role: Provides an intuitive interface for admins to manage modules with real-time feedback and dynamic updates.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', () => {
    /* ==================================================
       MESSAGE DISPLAY FUNCTION
       --------------------------------------------------
       Purpose: Displays temporary notification messages for module operations.
       Functionality: Updates a message div with the provided text and type, shows it, and hides it after 10 seconds.
       UI Role: Provides user feedback for actions like saving or deleting modules.
       Logic: Sets the message content and type, displays the message, and uses a timeout to hide it automatically.
    */
    function showMessage(message, type) {
        const messageDiv = document.getElementById('form-message');
        // Updates message content and applies type class (e.g., 'success' or 'error')
        messageDiv.textContent = message;
        messageDiv.className = `form-message ${type}`;
        // Makes the message visible
        messageDiv.style.display = 'block';
        // Hides the message after 10 seconds for a clean UI
        setTimeout(() => messageDiv.style.display = 'none', 10000);
    }

    /* ==================================================
       UPDATE MODULE COUNTERS
       --------------------------------------------------
       Purpose: Updates the UI based on the number of modules displayed.
       Functionality: Hides the table and shows a "No modules" message if no modules exist.
       UI Role: Informs users when no modules are registered, improving clarity.
       Logic: Counts table rows, hides the table if empty, and appends a "No modules" message to the section.
    */
    function updateModuleCounters() {
        // Gets all module rows in the table
        const rows = document.querySelectorAll('tbody tr');
        const totalModules = rows.length;
        if (totalModules === 0) {
            // Hides table container if no modules exist
            const tableContainer = document.querySelector('.modules-table-container');
            tableContainer.style.display = 'none';
            // Creates and appends a "No modules" message
            const noModulesDiv = document.createElement('div');
            noModulesDiv.className = 'no-modules';
            noModulesDiv.innerHTML = `<p>No modules registered.</p>`;
            document.querySelector('.modules-list-section').appendChild(noModulesDiv);
        }
    }

    /* ==================================================
       EDIT MODULE FUNCTION
       --------------------------------------------------
       Purpose: Populates the module form for editing an existing module.
       Functionality: Fetches module data by ID, fills form fields, and updates UI for editing mode.
       UI Role: Allows admins to edit module details seamlessly.
       Logic: Retrieves module data, sets form values, handles custom title input, and scrolls to the form.
    */
    let editingModuleId = null; // Tracks the ID of the module being edited
    window.editModule = function (id) {
        // Fetches module data from the server
        fetch(window.basePath + `/admin/api/modules/${id}`)
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    const module = response.data;
                    // Stores the module ID for editing
                    editingModuleId = module.id;

                    // Handles module title selection
                    const titleSelect = document.getElementById('module_title_select');
                    const titleOther = document.getElementById('module_title_other');
                    let found = false;
                    // Checks if the module title matches a select option
                    for (let i = 0; i < titleSelect.options.length; i++) {
                        if (titleSelect.options[i].value === module.title) {
                            titleSelect.value = module.title;
                            titleOther.value = '';
                            titleOther.style.display = 'none';
                            found = true;
                            break;
                        }
                    }
                    // If title not found, uses "other" option and shows custom input
                    if (!found) {
                        titleSelect.value = 'other';
                        titleOther.value = module.title;
                        titleOther.style.display = 'block';
                    }

                    // Populates form fields with module data
                    document.getElementById('description').value = module.description;
                    document.getElementById('year').value = module.year;
                    document.getElementById('credits').value = module.credits || 20;
                    document.getElementById('programme_id').value = module.programme_id;
                    document.getElementById('module_leader_id').value = module.module_leader_id || '';
                    document.getElementById('is_core').checked = module.is_core == 1 || module.is_core === true;
                    document.getElementById('is_active').checked = module.is_active == 1 || module.is_active === true;
                    // Populates associated staff in multi-select
                    const staffSelect = document.getElementById('module_staff_ids');
                    if (staffSelect && Array.isArray(module.staff_ids)) {
                        Array.from(staffSelect.options).forEach(option => {
                            option.selected = module.staff_ids.includes(option.value);
                        });
                    }
                    // Updates form title and button text for editing mode
                    document.getElementById('form-title').textContent = 'Edit Module';
                    document.getElementById('submit-text').textContent = 'Update Module';
                    document.getElementById('cancel-btn').style.display = 'inline-flex';
                    // Scrolls to the form for user convenience
                    document.querySelector('.module-form-section').scrollIntoView({ behavior: 'smooth' });
                } else {
                    // Shows error message if module loading fails
                    showMessage('Error loading module: ' + (response.message || 'Unknown error'), 'error');
                }
            })
            .catch(() => showMessage('Error loading module', 'error'));
    };

    /* ==================================================
       RESET FORM FUNCTION
       --------------------------------------------------
       Purpose: Resets the module form to its initial state.
       Functionality: Clears form fields, resets UI elements, and prepares for creating a new module.
       UI Role: Simplifies switching between edit and create modes.
       Logic: Resets form inputs, updates title and button text, hides cancel button, and sets default values.
    */
    window.resetForm = function () {
        // Resets the form to its initial state
        document.getElementById('module-form').reset();
        // Updates form title and button text for creation mode
        document.getElementById('form-title').textContent = 'Create New Module';
        document.getElementById('submit-text').textContent = 'Create Module';
        document.getElementById('cancel-btn').style.display = 'none';
        // Clears editing module ID
        editingModuleId = null;
        // Resets title select and custom input
        const titleSelect = document.getElementById('module_title_select');
        const titleOther = document.getElementById('module_title_other');
        if (titleSelect) {
            titleSelect.value = '';
        }
        if (titleOther) {
            titleOther.value = '';
            titleOther.style.display = 'none';
        }
        // Sets default values for checkboxes
        document.getElementById('is_core').checked = true;
        document.getElementById('is_active').checked = true;
        // Sets default credits value
        document.getElementById('credits').value = 20;
    };

    /* ==================================================
       DELETE MODULE FUNCTION
       --------------------------------------------------
       Purpose: Deletes a module from the database and updates the UI.
       Functionality: Sends a DELETE request, removes the module row with animation, and updates counters.
       UI Role: Allows admins to remove modules with confirmation and feedback.
       Logic: Confirms deletion, sends DELETE request, animates row removal, and refreshes the module list.
    */
    window.deleteModule = function (id, title) {
        // Prompts user for confirmation before deletion
        if (confirm(`Are you sure you want to delete the module "${title}" ? `)) {
            // Sends DELETE request to server
            fetch(window.basePath + `/admin/api/modules/${id}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(response => {
                    if (response.status === 'success') {
                        // Fades out the module row
                        const row = document.querySelector(`tr[data-module-id="${id}"]`);
                        row.style.opacity = '0';
                        // Removes row and updates UI after animation
                        setTimeout(() => {
                            row.remove();
                            updateModuleCounters();
                            showMessage('Module deleted successfully!', 'success');
                        }, 300);
                    } else {
                        // Shows error message if deletion fails
                        showMessage('Error deleting module: ' + (response.message || 'Unknown error'), 'error');
                    }
                })
                .catch(() => showMessage('Error deleting module', 'error'));
        }
    };

    /* ==================================================
       MODULE FORM SUBMISSION
       --------------------------------------------------
       Purpose: Handles creation and updating of modules via form submission.
       Functionality: Processes form data, sends POST/PUT requests, and updates the UI on success.
       UI Role: Enables admins to create or update modules with validation and feedback.
       Logic: Collects form data, handles custom title and multi-select staff, determines request type (POST/PUT), and refreshes the module list on success.
    */
    const form = document.getElementById('module-form');
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Prevents default form submission
        const formData = new FormData(form);
        // Collects selected staff IDs from multi-select
        const staffSelect = document.getElementById('module_staff_ids');
        let module_staff_ids = [];
        if (staffSelect) {
            module_staff_ids = Array.from(staffSelect.selectedOptions).map(opt => opt.value);
        }
        // Determines module title (select or custom)
        let module_title = '';
        const titleSelect = document.getElementById('module_title_select');
        const titleOther = document.getElementById('module_title_other');
        if (titleSelect && titleOther) {
            module_title = titleSelect.value === 'other' ? titleOther.value : titleSelect.value;
        }
        // Constructs data object for submission
        const data = {
            title: module_title,
            programme_id: formData.get('programme_id'),
            description: formData.get('description'),
            year: formData.get('year'),
            module_leader_id: formData.get('module_leader_id') || null,
            credits: formData.get('credits') || 20,
            is_core: formData.has('is_core'),
            is_active: formData.has('is_active'),
            module_staff_ids
        };
        // Determines if editing or creating a module
        const isEditing = editingModuleId !== null;
        const method = isEditing ? 'PUT' : 'POST';
        const url = isEditing ? `${window.basePath}/admin/api/modules/${editingModuleId}` : `${window.basePath}/admin/api/modules`;
        // Sends request to server
        fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    // Shows success message, resets form, and reloads modules
                    showMessage('Module saved successfully!', 'success');
                    resetForm();
                    loadModules();
                } else {
                    // Shows error message if saving fails
                    showMessage('Error saving module: ' + (response.message || 'Unknown error'), 'error');
                }
            })
            .catch(() => showMessage('Error saving module', 'error'));
    });

    /* ==================================================
       SEARCH FUNCTIONALITY
       --------------------------------------------------
       Purpose: Filters module rows based on search input.
       Functionality: Hides rows that don’t match the search term.
       UI Role: Allows admins to quickly find modules by title.
       Logic: Listens for input changes, compares lowercase titles with the search term, and toggles row visibility.
    */
    const searchInput = document.getElementById('module-search');
    searchInput.addEventListener('input', (e) => {
        // Gets search term in lowercase
        const term = e.target.value.toLowerCase();
        // Filters rows based on title match
        document.querySelectorAll('tbody tr').forEach(row => {
            const title = row.querySelector('.module-info h4').textContent.toLowerCase();
            row.style.display = title.includes(term) ? '' : 'none';
        });
    });

    /* ==================================================
       PROGRAMME FILTER
       --------------------------------------------------
       Purpose: Filters module rows by selected programme.
       Functionality: Shows only rows matching the selected programme ID or all if none selected.
       UI Role: Enables admins to view modules for a specific programme.
       Logic: Listens for select changes, compares row programme IDs, and toggles visibility.
    */
    const filterSelect = document.getElementById('filter-programme');
    filterSelect.addEventListener('change', (e) => {
        // Gets selected programme ID
        const programmeId = e.target.value;
        // Filters rows based on programme ID
        document.querySelectorAll('tbody tr').forEach(row => {
            const id = row.querySelector('.programme-badge').getAttribute('data-programme-id');
            row.style.display = !programmeId || id === programmeId ? '' : 'none';
        });
    });

    /* ==================================================
       CANCEL BUTTON
       --------------------------------------------------
       Purpose: Resets the form when the cancel button is clicked.
       Functionality: Triggers the resetForm function to clear the form.
       UI Role: Allows admins to cancel editing and return to create mode.
       Logic: Attaches a click event listener to the cancel button to call resetForm.
    */
    document.getElementById('cancel-btn').addEventListener('click', resetForm);

    /* ==================================================
       LOAD PROGRAMMES
       --------------------------------------------------
       Purpose: Populates programme select elements with data from the server.
       Functionality: Fetches programme data and updates form and filter selects.
       UI Role: Provides programme options for module creation and filtering.
       Logic: Fetches programmes, clears existing options, and adds new ones to both selects.
    */
    function loadProgrammes() {
        // Fetches programme data from server
        fetch(window.basePath + '/admin/api/programmes')
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    const programmes = response.data;
                    const programmeSelect = document.getElementById('programme_id');
                    const filterSelect = document.getElementById('filter-programme');
                    // Clears existing options
                    programmeSelect.innerHTML = '<option value="">Select a programme</option>';
                    filterSelect.innerHTML = '<option value="">All programmes</option>';
                    // Adds programme options to both selects
                    programmes.forEach(programme => {
                        const option = document.createElement('option');
                        option.value = programme.id;
                        option.textContent = programme.title;
                        programmeSelect.appendChild(option);
                        filterSelect.appendChild(option.cloneNode(true));
                    });
                } else {
                                    // Logs error if programme loading fails
                console.error('Error loading programmes:', response.message);
            }
        })
        .catch(error => {
            console.error('Error loading programmes:', error);
        });
    }

    /* ==================================================
       LOAD STAFF
       --------------------------------------------------
       Purpose: Populates staff select elements with data from the server.
       Functionality: Fetches staff data and updates leader and multi-select staff fields.
       UI Role: Provides staff options for module leader and associated staff assignments.
       Logic: Fetches staff, clears existing options, and populates both select elements.
    */
    function loadStaff() {
        // Fetches staff data from server
        fetch(window.basePath + '/admin/api/staff')
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    const staff = response.data;
                    const leaderSelect = document.getElementById('module_leader_id');
                    // Clears and populates leader select
                    leaderSelect.innerHTML = '<option value="">Select leader (optional)</option>';
                    staff.forEach(member => {
                        const option = document.createElement('option');
                        option.value = member.id;
                        option.textContent = member.title ? `${member.title} ${member.full_name}` : member.full_name;
                        leaderSelect.appendChild(option);
                    });
                    // Populates multi-select staff field
                    const staffSelect = document.getElementById('module_staff_ids');
                    if (staffSelect) {
                        staffSelect.innerHTML = '';
                        staff.forEach(member => {
                            const option = document.createElement('option');
                            option.value = member.id;
                            option.textContent = member.title ? `${member.title} ${member.full_name}` : member.full_name;
                            staffSelect.appendChild(option);
                        });
                    }
                } else {
                                console.error('Error loading staff members:', response.message);
        }
    })
    .catch(error => {
        console.error('Error loading staff members:', error);
            });
    }

    /* ==================================================
       RENDER PAGINATION
       --------------------------------------------------
       Purpose: Generates pagination controls for module list.
       Functionality: Creates numbered buttons for navigating pages, highlighting the current page.
       UI Role: Enables navigation through paginated module lists.
       Logic: Calculates total pages, creates buttons, and hides pagination if only one page exists.
    */
    function renderPagination(total, currentPage, pageSize = 4) {
        const container = document.querySelector('.modules-table-container');
        let pagination = container.querySelector('.pagination');
        // Creates pagination container if it doesn’t exist
        if (!pagination) {
            pagination = document.createElement('div');
            pagination.className = 'pagination';
            container.appendChild(pagination);
        }
        // Clears existing pagination buttons
        pagination.innerHTML = '';
        // Calculates total pages based on module count and page size
        const totalPages = Math.ceil(total / pageSize);
        // Creates a button for each page
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'btn' + (i === currentPage ? ' active' : '');
            btn.onclick = () => loadModules(i);
            pagination.appendChild(btn);
        }
        // Shows pagination only if multiple pages exist
        pagination.style.display = totalPages > 1 ? 'flex' : 'none';
    }

    /* ==================================================
       LOAD MODULES
       --------------------------------------------------
       Purpose: Fetches and displays paginated module data in a table.
       Functionality: Retrieves modules, renders table rows, and updates pagination and counters.
       UI Role: Displays a list of modules with details and action buttons.
       Logic: Fetches modules, slices data for pagination, creates table rows, and calls pagination and counter functions.
    */
    function loadModules(page = 1) {
        const pageSize = 4;
        // Fetches module data from server
        fetch(window.basePath + '/admin/api/modules')
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    const modules = response.data;
                    const tbody = document.querySelector('.modules-table tbody');
                    // Clears existing table content
                    tbody.innerHTML = '';
                    // Calculates start and end indices for pagination
                    const start = (page - 1) * pageSize;
                    const end = start + pageSize;
                    const paginatedModules = modules.slice(start, end);
                    // Creates a table row for each module
                    paginatedModules.forEach(module => {
                        const row = document.createElement('tr');
                        row.setAttribute('data-module-id', module.id);
                        row.innerHTML = `
                            <td>
                                <div class="module-info">
                                    <h4>${module.title}</h4>
                                    <p>${module.description}</p>
                                    <div class="module-badges">
                                        ${module.is_core == 1 || module.is_core === true ? '<span class="badge core">Core</span>' : '<span class="badge elective">Elective</span>'}
                                        ${module.is_active == 1 || module.is_active === true ? '<span class="badge active">Active</span>' : '<span class="badge inactive">Inactive</span>'}
                                    </div>
                                </div>
                            </td>
                            <td><span class="programme-badge" data-programme-id="${module.programme_id}">${module.programme_title}</span></td>
                            <td><span class="year-badge year-${module.year}">Year ${module.year}</span></td>
                            <td>
                                <div class="leader-info">
                                    <img src="${module.module_leader_id ? module.photo_url : 'https://via.placeholder.com/40x40/95a5a6/ffffff?text=NA'}" alt="${module.module_leader_full || 'Not assigned'}" class="leader-photo" onerror="this.onerror=null;this.src='/images/default-avatar.svg';">
                                    <span class="leader-badge">${module.module_leader_full || 'Not assigned'}</span>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn btn-edit" onclick="editModule(${module.id})" aria-label="Edit module ${module.title}">
                                        <i class="uil uil-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-delete" onclick="deleteModule(${module.id}, '${module.title}')" aria-label="Delete module ${module.title}">
                                        <i class="uil uil-trash-alt"></i>
                                    </button>
                                </td>
                            `;
                        tbody.appendChild(row);
                    });
                    // Updates pagination and module counters
                    renderPagination(modules.length, page, pageSize);
                    updateModuleCounters();
                } else {
                    // Logs error if module loading fails
                    console.error('Error loading modules:', response.message);
                }
            })
            .catch(error => {
                console.error('Error loading modules:', error);
            });
    }

    /* ==================================================
       EXISTING TITLE SELECT
       --------------------------------------------------
       Purpose: Autofills the title input when an existing title is selected.
       Functionality: Updates the title input based on the selected option.
       UI Role: Simplifies module creation by reusing existing titles.
       Logic: Listens for changes in the title select and sets the title input value.
    */
    const existingTitleSelect = document.getElementById('existing_module_title');
    if (existingTitleSelect) {
        existingTitleSelect.addEventListener('change', function () {
            // Gets the selected option’s text
            const selectedOption = existingTitleSelect.options[existingTitleSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                // Sets the title input to the selected text
                document.getElementById('title').value = selectedOption.text;
            }
        });
    }

    /* ==================================================
       TITLE SELECT TOGGLE
       --------------------------------------------------
       Purpose: Shows/hides the custom title input based on select value.
       Functionality: Displays the custom title input when "other" is selected.
       UI Role: Allows admins to enter custom module titles when needed.
       Logic: Listens for select changes, toggles the custom input’s visibility, and sets its required state.
    */
    const titleSelect = document.getElementById('module_title_select');
    const titleOther = document.getElementById('module_title_other');
    if (titleSelect && titleOther) {
        titleSelect.addEventListener('change', function () {
            if (this.value === 'other') {
                // Shows custom title input and makes it required
                titleOther.style.display = 'block';
                titleOther.required = true;
            } else {
                // Hides custom title input and removes required state
                titleOther.style.display = 'none';
                titleOther.required = false;
            }
        });
    }

    /* ==================================================
       INITIALIZATION
       --------------------------------------------------
       Purpose: Initializes the module management interface.
       Functionality: Loads programmes, staff, and modules on page load.
       UI Role: Sets up the module management section for immediate use.
       Logic: Calls functions to populate selects and load initial module data.
    */
    loadProgrammes();
    loadStaff();
    loadModules();
});