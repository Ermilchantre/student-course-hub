/* ==================================================
   STAFF DASHBOARD INTERACTIVITY JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages sidebar navigation, animations, tooltips, and search functionality in the admin dashboard.
   Functionality: Handles section toggling, active link updates, stat card animations, email links, table search, and counter updates.
   UI Role: Provides an interactive and responsive interface for navigating dashboard sections and viewing data.
   
   ==================================================
*/

document.addEventListener('DOMContentLoaded', () => {
    /* ==================================================
       SIDEBAR NAVIGATION SETUP
       --------------------------------------------------
       Purpose: Initializes sidebar navigation for switching between dashboard sections.
       Functionality: Toggles visibility of content sections and updates active link states.
       UI Role: Enables seamless navigation between dashboard sections with visual feedback.
       Logic: Listens for clicks on sidebar links, shows the target section, and updates active states.
    */
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    const contentSections = document.querySelectorAll('.content-section');

    // Function to show a specific section with animation
    function showSection(sectionId) {
        // Hides all sections and removes fade-in animation
        contentSections.forEach(section => {
            section.style.display = 'none';
            section.classList.remove('fade-in');
        });

        // Shows the selected section
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.style.display = 'block';
            // Adds fade-in animation after a short delay
            setTimeout(() => targetSection.classList.add('fade-in'), 50);
            // Scrolls smoothly to the section
            setTimeout(() => targetSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            }), 10);
        }
    }

    // Function to update the active link state
    function updateActiveLink(clickedLink) {
        // Removes active class from all links
        navLinks.forEach(link => link.classList.remove('active'));
        // Adds active class to the clicked link
        clickedLink.classList.add('active');
    }

    // Sets up click listeners for sidebar navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            // Extracts target section ID from href
            const targetId = this.getAttribute('href').substring(1);
            // Updates active link
            updateActiveLink(this);
            // Shows the corresponding section
            showSection(targetId);
        });
    });

    // Shows dashboard overview by default
    showSection('dashboard-overview');
    updateActiveLink(document.querySelector('.nav-link[href="#dashboard"]'));

    /* ==================================================
       EMAIL LINKS
       --------------------------------------------------
       Purpose: Enhances email link interactions.
       Functionality: Logs email link clicks for debugging or tracking.
       UI Role: Ensures email links function as expected with potential for future enhancements.
       Logic: Listens for clicks on mailto links and logs the href.
    */
    const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
    emailLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Logs email link click for debugging
            console.log('Opening email client for:', this.href);
        });
    });

    /* ==================================================
       TOOLTIP INITIALIZATION
       --------------------------------------------------
       Purpose: Initializes Bootstrap tooltips for additional information.
       Functionality: Activates tooltips for elements with data-bs-toggle="tooltip".
       UI Role: Provides contextual information on hover or focus.
       Logic: Maps tooltip elements and initializes them using Bootstrap's Tooltip class.
    */
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    /* ==================================================
       STATS CARD ANIMATIONS
       --------------------------------------------------
       Purpose: Animates stats cards when they enter the viewport.
       Functionality: Applies fade-in and translate animations using IntersectionObserver.
       UI Role: Enhances visual appeal with on-scroll animations for stats cards.
       Logic: Observes cards and applies opacity and transform styles when visible.
    */
    const statsCards = document.querySelectorAll('.stats-card');
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -50px 0px'
    };
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Animates card into view
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Initializes stats card animations
    statsCards.forEach(card => {
        // Sets initial styles for animation
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        // Observes card for visibility
        observer.observe(card);
    });

    /* ==================================================
       INTERESTS TABLE SEARCH
       --------------------------------------------------
       Purpose: Filters interests table rows based on search input.
       Functionality: Shows/hides table rows matching the search term.
       UI Role: Enables quick searching within the interests table.
       Logic: Listens for input changes and filters rows by text content.
    */
    const searchInput = document.getElementById('interests-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#interests tbody tr');
            // Filters rows based on search term
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    /* ==================================================
       COUNTER UPDATES
       --------------------------------------------------
       Purpose: Updates the interests counter in the stats card.
       Functionality: Counts visible table rows and updates the counter display.
       UI Role: Keeps the interests count in sync with the table data.
       Logic: Counts rows in the interests table and updates the stats card.
    */
    function updateCounters() {
        // Counts rows in the interests table
        const studentInterestsCount = document.querySelectorAll('#interests tbody tr').length;
        const interestsCounter = document.querySelector('.stats-card:nth-child(3) h4');
        if (interestsCounter) {
            // Updates counter display
            interestsCounter.textContent = studentInterestsCount;
        }
    }

    // Calls updateCounters on page load
    updateCounters();

    /* ==================================================
       OVERVIEW CARD INTERACTIONS
       --------------------------------------------------
       Purpose: Adds hover and click effects to overview cards.
       Functionality: Applies hover animations and navigates to sections on click.
       UI Role: Enhances interactivity for overview cards with visual feedback.
       Logic: Adds hover effects and navigates to corresponding sections on click.
    */
    const overviewCards = document.querySelectorAll('#dashboard-overview .text-center');
    overviewCards.forEach((card, index) => {
        card.addEventListener('mouseenter', function () {
            // Applies lift effect on hover
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });

        card.addEventListener('mouseleave', function () {
            // Resets transform on leave
            this.style.transform = 'translateY(0)';
        });

        card.addEventListener('click', function () {
            // Maps card index to section IDs
            const sections = ['modules, rogrammes', 'interests'];
            const sectionId = sections[index];

            // Updates sidebar link and shows section
            const correspondingLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);
            if (correspondingLink) {
                updateActiveLink(correspondingLink);
            }
            showSection(sectionId);
        });
    });
});