/* ==================================================
   MODULE AND PROFESSOR DISPLAY JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages the display of modules by year, module leader details, professor lists, and collapsible sections.
   Functionality: Handles year-based module toggling, sidebar leader info, professor list rendering, and accordion behavior.
   UI Role: Provides an interactive interface for viewing module details, leader information, and associated professors.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', function () {
    /* ==================================================
       INITIAL SETUP
       --------------------------------------------------
       Purpose: Initializes the module display on page load.
       Functionality: Hides module content, clears leader info, and shows a default message.
       UI Role: Prepares the UI for module interactions with a clean initial state.
       Logic: Hides all module content, clears sidebar info, and displays a no-leader message.
    */
    const yearHeaders = document.querySelectorAll('.year-modules h3');
    const allModulesContents = document.querySelectorAll('.modules-content');
    const moduleLeaderInfo = document.getElementById('module-leader-info');
    const noLeaderMessage = document.getElementById('no-leader-message');
    // Retrieves base path from body attribute
    const basePath = document.body.getAttribute('data-base-path') || '';

    // Hides all module content sections
    allModulesContents.forEach(content => content.style.display = 'none');
    if (moduleLeaderInfo) {
        // Clears leader info sidebar
        moduleLeaderInfo.innerHTML = '';
    }
    if (noLeaderMessage) {
        // Shows default no-leader message
        noLeaderMessage.style.display = 'block';
    }

    /* ==================================================
       YEAR HEADER TOGGLE
       --------------------------------------------------
       Purpose: Toggles module content visibility for a selected year.
       Functionality: Shows/hides modules for a clicked year and updates sidebar with leader info.
       UI Role: Allows users to view modules by year and see leader details in the sidebar.
       Logic: Toggles module visibility, populates leader info, processes professor lists, and scrolls to the header.
    */
    yearHeaders.forEach(header => {
        // Sets cursor to indicate interactivity
        header.style.cursor = 'pointer';
        header.addEventListener('click', function (event) {
            event.preventDefault();
            // Extracts year from header text
            const year = header.textContent.match(/\d+/)[0];
            const modulesContent = document.getElementById(`year-${year}-modules`);
            const isVisible = modulesContent && modulesContent.style.display === 'block';

            if (isVisible) {
                // Hides modules and resets sidebar
                modulesContent.style.display = 'none';
                if (moduleLeaderInfo) {
                    moduleLeaderInfo.innerHTML = '';
                }
                if (noLeaderMessage) {
                    noLeaderMessage.style.display = 'block';
                }
            } else {
                // Hides all other module content
                allModulesContents.forEach(content => content.style.display = 'none');
                // Shows modules for the selected year
                if (modulesContent) {
                    modulesContent.style.display = 'block';
                }

                // Displays leader info for the first module in the sidebar
                const firstModule = modulesContent.querySelector('.module');
                if (firstModule && moduleLeaderInfo) {
                    // Retrieves leader data from module attributes
                    const leaderPhoto = firstModule.getAttribute('data-leader-photo');
                    const leaderName = firstModule.getAttribute('data-leader-name');
                    const leaderTitle = firstModule.getAttribute('data-leader-title');
                    const leaderDepartment = firstModule.getAttribute('data-leader-department');
                    const leaderSpecialization = firstModule.getAttribute('data-leader-specialization');

                    // Constructs correct photo path
                    let photoPath = leaderPhoto;
                    if (leaderPhoto && !leaderPhoto.startsWith('http') && !leaderPhoto.startsWith('data:')) {
                        photoPath = basePath + (leaderPhoto.startsWith('/') ? leaderPhoto : '/' + leaderPhoto);
                    } else if (!leaderPhoto) {
                        photoPath = basePath + '/images/default-avatar.svg';
                    }

                    // Creates HTML for leader info in the sidebar
                    const leaderHTML = `
                        <div class="module-leader-sidebar-info">
                            <img src="${photoPath}" alt="Leader Photo" onerror="this.onerror=null;this.src=basePath+'/images/default-avatar.svg';">
                            <div class="leader-details">
                                <strong>${leaderTitle} ${leaderName}</strong>
                                <span>Dept: ${leaderDepartment}</span>
                                <span>Spec: ${leaderSpecialization}</span>
                            </div>
                        </div>
                    `;

                    moduleLeaderInfo.innerHTML = leaderHTML;
                    if (noLeaderMessage) {
                        noLeaderMessage.style.display = 'none';
                    }
                }

                // Processes professor lists for each module
                modulesContent.querySelectorAll('.module').forEach(moduleCard => {
                    const professorsData = moduleCard.getAttribute('data-professors');
                    const professorsList = moduleCard.querySelector('.professors-list');

                    if (professorsData && professorsList) {
                        try {
                            // Parses JSON data for professors
                            const professors = JSON.parse(professorsData);
                            let professorsHTML = '';

                            if (professors.length > 0) {
                                // Generates HTML for each professor
                                professors.forEach(prof => {
                                    let photoPath = prof.photo_url || basePath + '/images/default-avatar.svg';
                                    if (photoPath && !photoPath.startsWith('http') && !photoPath.startsWith('data:')) {
                                        photoPath = basePath + (photoPath.startsWith('/') ? photoPath : '/' + photoPath);
                                    }

                                    professorsHTML += `
                                        <div class="professor-item">
                                            <div class="professor-photo">
                                                <img src="${photoPath}" alt="Professor Photo" onerror="this.onerror=null;this.src=basePath+'/images/default-avatar.svg';">
                                            </div>
                                            <div class="professor-details">
                                                <p><strong>${prof.title || 'Professor'} ${prof.full_name}</strong></p>
                                                <p>${prof.department}</p>
                                                <p>${prof.specialization}</p>
                                                <p><i class="uil uil-envelope"></i> ${prof.email}</p>
                                            </div>
                                        </div>
                                    `;
                                });
                            } else {
                                // Displays message if no professors are assigned
                                professorsHTML = '<p>No additional professors assigned to this module.</p>';
                            }

                            // Updates professor list in the module card
                            professorsList.innerHTML = professorsHTML;
                        } catch (e) {
                            // Handles JSON parsing errors
                            console.log('Error parsing professors data:', e);
                            professorsList.innerHTML = '<p>No additional professors assigned to this module.</p>';
                        }
                    }
                });
            }
            // Scrolls to the clicked header
            header.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    /* ==================================================
       ACCORDION FOR COLLAPSIBLE SECTIONS
       --------------------------------------------------
       Purpose: Toggles visibility of collapsible content sections.
       Functionality: Shows/hides content under headers like Course Information and Teaching Staff.
       UI Role: Provides an accordion interface for viewing additional module details.
       Logic: Toggles display style of content sections when headers are clicked.
    */
    document.querySelectorAll('.collapsible-header').forEach(header => {
        // Sets cursor to indicate interactivity
        header.style.cursor = 'pointer';
        header.addEventListener('click', function () {
            // Toggles visibility of the next sibling content
            const content = this.nextElementSibling;
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    });

    /* ==================================================
       INFO CARDS TOGGLE FUNCTIONALITY
       --------------------------------------------------
       Purpose: Handles expansion and collapse of Entry Requirements and Career Prospects cards
       Functionality: Toggles card content visibility with smooth animations
       UI Role: Provides interactive card expansion for better UX
    */
    
    // Make toggleCard function globally available
    window.toggleCard = function(card) {
        const details = card.querySelector('.card-details');
        const summary = card.querySelector('.card-summary');
        const toggleIcon = card.querySelector('.toggle-icon');
        const isExpanded = card.classList.contains('expanded');
        
        // Prevent event bubbling
        event.stopPropagation();
        
        if (isExpanded) {
            // Collapse card
            card.classList.remove('expanded');
            details.style.display = 'none';
            details.classList.remove('show');
            summary.style.display = 'block';
            
            // Reset icon rotation
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(0deg)';
            }
        } else {
            // Expand card
            card.classList.add('expanded');
            summary.style.display = 'none';
            details.style.display = 'block';
            details.classList.add('show');
            
            // Rotate icon
            if (toggleIcon) {
                toggleIcon.style.transform = 'rotate(180deg)';
            }
        }
    };

    // Add event listeners to info cards
    const infoCards = document.querySelectorAll('.info-card');
    
    infoCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Toggle card functionality
            const details = card.querySelector('.card-details');
            const summary = card.querySelector('.card-summary');
            const toggleIcon = card.querySelector('.toggle-icon');
            const isExpanded = card.classList.contains('expanded');
            
            if (isExpanded) {
                // Collapse card
                card.classList.remove('expanded');
                details.classList.remove('show');
                summary.style.display = 'block';
                
                setTimeout(() => {
                    details.style.display = 'none';
                }, 300);
                
            } else {
                // Expand card
                card.classList.add('expanded');
                summary.style.display = 'none';
                details.style.display = 'block';
                
                setTimeout(() => {
                    details.classList.add('show');
                }, 10);
            }
            
            // Add ripple effect
            const existingRipple = card.querySelector('.ripple');
            if (existingRipple) {
                existingRipple.remove();
            }
            
            const ripple = document.createElement('span');
            const rect = card.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            card.appendChild(ripple);
            
            setTimeout(() => {
                if (ripple.parentNode) {
                    ripple.remove();
                }
            }, 600);
        });
    });
});