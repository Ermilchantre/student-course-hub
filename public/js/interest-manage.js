/* ==================================================
   STUDENT INTEREST MANAGEMENT JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages student interest data, including filtering, pagination, selection, and deletion in the admin dashboard.
   Functionality: Handles search and programme filtering, paginated table rendering, bulk deletion, and Excel export.
   UI Role: Provides an intuitive interface for admins to view, filter, and manage student interests with real-time feedback.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', () => {
    /* ==================================================
       GLOBAL VARIABLES
       --------------------------------------------------
       Purpose: Stores state for pagination, selection, and filtering.
       Functionality: Tracks current page, page size, selected interest IDs, interest data, and filter settings.
       UI Role: Maintains state for consistent table rendering and user interactions.
       Logic: Initializes variables to manage pagination, selection, and filter state across functions.
    */
    let currentPage = 1; // Tracks the current page for pagination
    let pageSize = 5; // Number of interests per page
    let selectedIds = new Set(); // Stores IDs of selected interests
    let allInterests = []; // Stores fetched interest data
    let currentFilters = {
        search: '', // Search term for filtering
        programmeId: '' // Selected programme ID for filtering
    };

    /* ==================================================
       DOM ELEMENTS
       --------------------------------------------------
       Purpose: References key DOM elements for interaction.
       Functionality: Stores references to input fields, buttons, and modal elements.
       UI Role: Enables direct manipulation of UI components.
       Logic: Selects elements for search, filtering, selection, and confirmation modal interactions.
    */
    const searchInput = document.getElementById('search-input');
    const programmeFilter = document.getElementById('programme-filter');
    const exportExcelBtn = document.getElementById('export-excel-btn');
    const deleteSelectedBtn = document.getElementById('delete-selected-btn');
    const selectAllCheckbox = document.getElementById('select-all');
    const confirmModal = document.getElementById('confirm-modal');
    const confirmTitle = document.getElementById('confirm-title');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmYes = document.getElementById('confirm-yes');
    const confirmNo = document.getElementById('confirm-no');

    /* ==================================================
       INITIALIZATION
       --------------------------------------------------
       Purpose: Sets up the interest management interface on page load.
       Functionality: Loads programmes, statistics, interests, and event listeners.
       UI Role: Ensures the interface is fully populated and interactive.
       Logic: Calls initialization functions to prepare the UI and data.
    */
    init();
    function init() {
        loadProgrammes(); // Loads programme options for filtering
        loadStats(); // Loads statistical data
        loadInterests(); // Loads initial interest data
        setupEventListeners(); // Sets up event listeners for UI interactions
    }

    /* ==================================================
       EVENT LISTENERS SETUP
       --------------------------------------------------
       Purpose: Attaches event listeners to UI elements.
       Functionality: Handles input changes, button clicks, and modal interactions.
       UI Role: Enables dynamic filtering, selection, and confirmation actions.
       Logic: Listens for search input, programme filter changes, export, delete, and modal actions.
    */
    function setupEventListeners() {
        // Search input with debounced filtering
        searchInput.addEventListener('input', debounce(() => {
            currentFilters.search = searchInput.value;
            currentPage = 1; // Resets to first page on new search
            loadInterests();
        }, 300));

        // Programme filter change
        programmeFilter.addEventListener('change', () => {
            currentFilters.programmeId = programmeFilter.value;
            currentPage = 1; // Resets to first page on filter change
            loadInterests();
        });

        // Export and delete buttons
        exportExcelBtn.addEventListener('click', exportData);
        deleteSelectedBtn.addEventListener('click', deleteSelected);

        // Select all checkbox
        selectAllCheckbox.addEventListener('change', toggleSelectAll);

        // Confirmation modal buttons
        confirmYes.addEventListener('click', confirmAction);
        confirmNo.addEventListener('click', closeModal);

        // Closes modal when clicking outside
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                closeModal();
            }
        });
    }

    /* ==================================================
       MESSAGE DISPLAY FUNCTION
       --------------------------------------------------
       Purpose: Displays temporary notification messages.
       Functionality: Shows styled messages with animations for success, error, or info.
       UI Role: Provides visual feedback for user actions.
       Logic: Creates a message div, styles it based on type, animates it, and removes it after 5 seconds.
    */
    function showMessage(message, type = 'info') {
        // Creates a new message div
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.textContent = message;
        // Applies styles for positioning, appearance, and animation
        messageDiv.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem;
            border-radius: 8px; color: white; font-weight: 600; z-index: 1000;
            background: ${type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db'};
            transform: translateX(100%); transition: transform 0.3s ease;
        `;
        document.body.appendChild(messageDiv);
        // Slides in the message
        setTimeout(() => messageDiv.style.transform = 'translateX(0)', 100);
        // Slides out and removes the message after 5 seconds
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    }

    /* ==================================================
       DEBOUNCE FUNCTION
       --------------------------------------------------
       Purpose: Delays function execution to optimize performance.
       Functionality: Prevents rapid successive calls by waiting for a pause.
       UI Role: Improves responsiveness for search input handling.
       Logic: Uses a timeout to delay function execution until no new calls occur within the wait period.
    */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /* ==================================================
       LOAD PROGRAMMES
       --------------------------------------------------
       Purpose: Populates the programme filter dropdown with data.
       Functionality: Fetches programme data and adds options to the select element.
       UI Role: Enables filtering interests by programme.
       Logic: Fetches programmes, clears existing options, and appends new ones.
    */
    function loadProgrammes() {
        // Fetches programme data from the server
        fetch(`${window.basePath}/admin/api/programmes`)
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    const programmes = response.data;
                    // Clears existing options and adds default
                    programmeFilter.innerHTML = '<option value="">All Programmes</option>';
                    // Adds programme options to the filter dropdown
                    programmes.forEach(programme => {
                        const option = document.createElement('option');
                        option.value = programme.id;
                        option.textContent = programme.title;
                        programmeFilter.appendChild(option);
                    });
                }
            })
            .catch(() => showMessage('Error loading programmes', 'error'));
    }

    /* ==================================================
       LOAD STATISTICS
       --------------------------------------------------
       Purpose: Updates dashboard statistics for interests.
       Functionality: Fetches and displays total interests, programme counts, and monthly data.
       UI Role: Provides quick insights into interest data.
       Logic: Fetches stats with optional programme filter, updates stat displays, and handles monthly data.
    */
    function loadStats() {
        // Builds query parameters for filtering
        const params = new URLSearchParams();
        if (currentFilters.programmeId) {
            params.append('programme_id', currentFilters.programmeId);
        }
        // Fetches statistics from the server
        fetch(`${window.basePath}/admin/api/interests/stats?${params}`)
            .then(res => res.json())
            .then(response => {
                if (response.status === 'success') {
                    const stats = response.data;
                    // Updates total interests and programme counts
                    document.getElementById('total-interests-stat').textContent = stats.total;
                    document.getElementById('total-programmes-stat').textContent = stats.by_programme.length;
                    // Calculates and displays interests for the current month
                    const thisMonth = new Date().toISOString().slice(0, 7);
                    const thisMonthData = stats.by_month.find(item => item.month === thisMonth);
                    document.getElementById('this-month-stat').textContent = thisMonthData ? thisMonthData.count : 0;
                }
            })
            .catch(() => {
                // Silent error handling to avoid user disruption
                // console.error('Error loading stats:', error);
            });
    }

    /* ==================================================
       LOAD INTERESTS
       --------------------------------------------------
       Purpose: Fetches and displays paginated interest data.
       Functionality: Retrieves interests based on filters and renders them in a table.
       UI Role: Displays a paginated list of student interests with selection options.
       Logic: Builds query parameters, fetches data, renders table, updates pagination, and optionally scrolls to table.
    */
    function loadInterests(scrollToTable = false) {
        // Builds query parameters for pagination and filters
        const params = new URLSearchParams({
            page: currentPage,
            pageSize: pageSize
        });
        if (currentFilters.search) {
            params.append('q', currentFilters.search);
        }
        if (currentFilters.programmeId) {
            params.append('programme_id', currentFilters.programmeId);
        }
        // Fetches interest data from the server
        fetch(`${window.basePath}/admin/api/interests/search-advanced?${params}`)
            .then(res => res.json())
            .then(data => {
                // Stores fetched interests and renders table
                allInterests = data.results;
                renderInterests();
                // Updates pagination with server data
                renderPagination(data.total, data.page, data.pageSize, data.totalPages);
                loadStats(); // Updates statistics
                if (scrollToTable) {
                    // Scrolls to interests section if requested
                    const interestsSection = document.querySelector('.interests-section');
                    if (interestsSection) {
                        interestsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            })
            .catch(() => showMessage('Error loading interests', 'error'));
    }

    /* ==================================================
       RENDER INTERESTS
       --------------------------------------------------
       Purpose: Renders interest data in a table.
       Functionality: Creates table rows for interests, including checkboxes and delete buttons.
       UI Role: Displays interest details with selection and action options.
       Logic: Clears table, displays "no data" if empty, creates rows with escaped HTML, and sets up row event listeners.
    */
    function renderInterests() {
        const tbody = document.querySelector('.interests-table tbody');
        // Clears existing table content
        tbody.innerHTML = '';
        if (allInterests.length === 0) {
            // Displays a "no data" message if no interests are found
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="no-data">
                        <i class="uil uil-info-circle"></i>
                        No interests found
                    </td>
                </tr>
            `;
            return;
        }
        // Creates a table row for each interest
        allInterests.forEach(interest => {
            const row = document.createElement('tr');
            row.setAttribute('data-interest-id', interest.id);
            const isSelected = selectedIds.has(interest.id);
            // Formats registration date or uses 'N/A'
            const registrationDate = interest.created_at ? new Date(interest.created_at).toLocaleDateString() : 'N/A';
            row.innerHTML = `
                <td class="checkbox-column">
                    <input type="checkbox" class="interest-checkbox" value="${interest.id}" ${isSelected ? 'checked' : ''}>
                </td>
                <td>${escapeHtml(interest.student_name)}</td>
                <td>${escapeHtml(interest.email)}</td>
                <td>${escapeHtml(interest.programme_title)}</td>
                <td>${registrationDate}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${interest.id}" data-name="${escapeHtml(interest.student_name)}">
                        <i class="uil uil-trash-alt"></i> Delete
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
        // Sets up event listeners for checkboxes and delete buttons
        setupRowEventListeners();
        updateDeleteSelectedButton();
    }

    /* ==================================================
       ROW EVENT LISTENERS
       --------------------------------------------------
       Purpose: Sets up event listeners for table row interactions.
       Functionality: Handles checkbox selection and delete button clicks.
       UI Role: Enables selection of interests and individual deletion.
       Logic: Adds listeners to checkboxes for selection and delete buttons for confirmation modals.
    */
    function setupRowEventListeners() {
        // Handles checkbox selection
        document.querySelectorAll('.interest-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const id = parseInt(e.target.value);
                // Adds or removes ID from selected set
                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateSelectAllState();
                updateDeleteSelectedButton();
            });
        });
        // Handles delete button clicks
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(e.target.closest('.delete-btn').dataset.id);
                const name = e.target.closest('.delete-btn').dataset.name;
                // Shows confirmation modal for deletion
                showConfirmModal(
                    'Delete Interest',
                    `Are you sure you want to delete the interest for "${name}"?`,
                    () => deleteInterest(id)
                );
            });
        });
    }

    /* ==================================================
       RENDER PAGINATION
       --------------------------------------------------
       Purpose: Generates pagination controls for the interest table.
       Functionality: Creates previous, next, and numbered page buttons with ellipsis for large ranges.
       UI Role: Allows navigation through paginated interest data.
       Logic: Adds previous/next buttons and page numbers, showing only relevant pages with ellipsis for gaps.
    */
    function renderPagination(total, pageFromAPI, pageSize, totalPages) {
        const paginationContainer = document.getElementById('pagination');
        // Clears existing pagination
        paginationContainer.innerHTML = '';
        if (totalPages <= 1) {
            return;
        }
        // Adds previous button if not on first page
        if (pageFromAPI > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.className = 'btn btn-secondary';
            prevBtn.innerHTML = '<i class="uil uil-arrow-left"></i> Previous';
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = pageFromAPI - 1;
                loadInterests(true);
            });
            paginationContainer.appendChild(prevBtn);
        }
        // Adds page number buttons with ellipsis for gaps
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= pageFromAPI - 2 && i <= pageFromAPI + 2)) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `btn ${i === pageFromAPI ? 'btn-primary' : 'btn-secondary'}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    loadInterests(true);
                });
                paginationContainer.appendChild(pageBtn);
            } else if (i === pageFromAPI - 3 || i === pageFromAPI + 3) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'pagination-ellipsis';
                paginationContainer.appendChild(ellipsis);
            }
        }
        // Adds next button if not on last page
        if (pageFromAPI < totalPages) {
            const nextBtn = document.createElement('button');
            nextBtn.className = 'btn btn-secondary';
            nextBtn.innerHTML = 'Next <i class="uil uil-arrow-right"></i>';
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = pageFromAPI + 1;
                loadInterests(true);
            });
            paginationContainer.appendChild(nextBtn);
        }
    }

    /* ==================================================
       EXPORT DATA
       --------------------------------------------------
       Purpose: Exports interest data to an Excel file.
       Functionality: Initiates a file download with filtered data.
       UI Role: Allows admins to export interest data for external use.
       Logic: Builds a URL with filters, creates a temporary download link, and triggers it.
    */
    function exportData() {
        // Builds query parameters for export
        const params = new URLSearchParams({ format: 'excel' });
        if (currentFilters.programmeId) {
            params.append('programme_id', currentFilters.programmeId);
        }
        const url = `${window.basePath}/admin/api/interests/export?${params}`;
        // Creates a temporary link for download
        const link = document.createElement('a');
        link.href = url;
        link.download = `interests_${new Date().toISOString().slice(0, 10)}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        // Shows success message
        showMessage('Exporting Excel file...', 'success');
    }

    /* ==================================================
       DELETE INTEREST
       --------------------------------------------------
       Purpose: Deletes a single interest from the database.
       Functionality: Sends a DELETE request and refreshes the interest list.
       UI Role: Allows admins to remove individual interests with feedback.
       Logic: Sends DELETE request, removes ID from selection, and reloads interests on success.
    */
    function deleteInterest(id) {
        // Sends DELETE request for the interest
        fetch(`${window.basePath}/admin/api/interests/${id}`, {
            method: 'DELETE'
        })
            .then(response => {
                if (response.ok) {
                    // Shows success message and updates UI
                    showMessage('Interest deleted successfully', 'success');
                    selectedIds.delete(id);
                    loadInterests();
                } else {
                    // Handles server error response
                    return response.json().then(data => {
                        throw new Error(data.error || 'Failed to delete interest');
                    });
                }
            })
            .catch(() => showMessage('Error deleting interest', 'error'));
    }

    /* ==================================================
       DELETE SELECTED INTERESTS
       --------------------------------------------------
       Purpose: Deletes multiple selected interests.
       Functionality: Sends a POST request with selected IDs and refreshes the list.
       UI Role: Enables bulk deletion of interests with confirmation.
       Logic: Checks for selected IDs, shows confirmation modal, sends deletion request, and clears selection.
    */
    function deleteSelected() {
        // Checks if any interests are selected
        if (selectedIds.size === 0) {
            showMessage('No interests selected', 'error');
            return;
        }
        // Shows confirmation modal for bulk deletion
        showConfirmModal(
            'Delete Selected',
            `Are you sure you want to delete ${selectedIds.size} selected interest(s)? This action cannot be undone.`,
            () => {
                // Sends POST request to delete multiple interests
                fetch(`${window.basePath}/admin/api/interests/delete-multiple`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ids: Array.from(selectedIds)
                    })
                })
                    .then(res => res.json())
                    .then(response => {
                        if (response.status === 'success') {
                            // Shows success message, clears selection, and reloads
                            showMessage(response.message, 'success');
                            selectedIds.clear();
                            loadInterests();
                        } else {
                            showMessage('Error deleting interests', 'error');
                        }
                    })
                    .catch(() => showMessage('Error deleting interests', 'error'));
            }
        );
    }

    /* ==================================================
       TOGGLE SELECT ALL
       --------------------------------------------------
       Purpose: Toggles selection of all interest checkboxes.
       Functionality: Selects or deselects all interests and updates the selection set.
       UI Role: Simplifies bulk selection for deletion.
       Logic: Updates checkboxes and selectedIds based on select-all checkbox state.
    */
    function toggleSelectAll() {
        const isChecked = selectAllCheckbox.checked;
        // Updates all checkboxes and selection set
        document.querySelectorAll('.interest-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
            const id = parseInt(checkbox.value);
            if (isChecked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        // Updates delete button visibility
        updateDeleteSelectedButton();
    }

    /* ==================================================
       UPDATE SELECT ALL STATE
       --------------------------------------------------
       Purpose: Updates the select-all checkbox based on individual selections.
       Functionality: Checks if all checkboxes are selected and updates the select-all checkbox.
       UI Role: Ensures the select-all checkbox reflects the current selection state.
       Logic: Compares the number of checked checkboxes to the total and updates the checkbox state.
    */
    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll('.interest-checkbox');
        const checkedCount = document.querySelectorAll('.interest-checkbox:checked').length;
        // Sets select-all checkbox if all are checked
        selectAllCheckbox.checked = checkedCount === checkboxes.length && checkboxes.length > 0;
    }

    /* ==================================================
       UPDATE DELETE SELECTED BUTTON
       --------------------------------------------------
       Purpose: Updates the visibility and text of the delete selected button.
       Functionality: Shows or hides the button based on selected items and displays count.
       UI Role: Indicates when bulk deletion is available and how many items are selected.
       Logic: Toggles button visibility and updates text with selection count.
    */
    function updateDeleteSelectedButton() {
        if (selectedIds.size > 0) {
            // Shows button with selection count
            deleteSelectedBtn.style.display = 'inline-flex';
            deleteSelectedBtn.innerHTML = `<i class="uil uil-trash-alt"></i> Delete Selected (${selectedIds.size})`;
        } else {
            // Hides button if no items are selected
            deleteSelectedBtn.style.display = 'none';
        }
    }

    /* ==================================================
       CONFIRMATION MODAL
       --------------------------------------------------
       Purpose: Manages the confirmation modal for delete actions.
       Functionality: Displays a modal with title and message, storing a callback for confirmation.
       UI Role: Provides a confirmation step for destructive actions.
       Logic: Updates modal content, stores callback, and handles confirm/cancel actions.
    */
    let currentConfirmCallback = null;
    function showConfirmModal(title, message, onConfirm) {
        // Updates modal title and message
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        // Shows the modal
        confirmModal.style.display = 'flex';
        // Stores the confirmation callback
        currentConfirmCallback = onConfirm;
    }

    function confirmAction() {
        // Executes the stored callback and closes the modal
        if (currentConfirmCallback) {
            currentConfirmCallback();
        }
        closeModal();
    }

    function closeModal() {
        // Hides the modal and clears the callback
        confirmModal.style.display = 'none';
        currentConfirmCallback = null;
    }

    /* ==================================================
       HTML ESCAPE FUNCTION
       --------------------------------------------------
       Purpose: Escapes HTML characters to prevent XSS attacks.
       Functionality: Converts text to safe HTML by using a temporary div.
       UI Role: Ensures displayed data is safe from malicious input.
       Logic: Creates a div, sets text content, and returns escaped HTML.
    */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});