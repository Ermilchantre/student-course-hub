/* ==================================================
   ADMIN DASHBOARD JAVASCRIPT
   --------------------------------------------------
   Purpose: Provides interactivity for the admin dashboard, including navigation, animations, search functionality, and notifications.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

/* ==================================================
   DOM ELEMENTS
   --------------------------------------------------
   Purpose: Selects key DOM elements for navigation menu interactions.
   Functionality: Stores references to the navigation bar, menu buttons, and menu container for event handling.
   UI Role: Enables dynamic control of the navigation menu.
*/
const nav = document.querySelector('nav');
const openMenuBtn = document.querySelector('#open-menu-btn');
const closeMenuBtn = document.querySelector('#close-menu-btn');
const navMenu = document.querySelector('.nav_menu');

/* ==================================================
   NAVIGATION MENU TOGGLE
   --------------------------------------------------
   Purpose: Manages the opening and closing of the navigation menu on smaller screens.
   Functionality: Toggles the menu visibility and button states for mobile/tablet views (≤1024px).
   UI Role: Provides a responsive navigation experience for mobile users.
*/
const openNav = () => {
    if (window.innerWidth <= 1024) {
        navMenu.style.display = 'flex';
        openMenuBtn.style.display = 'none';
        closeMenuBtn.style.display = 'inline-block';
        navMenu.classList.add('open');
    }
}

const closeNav = () => {
    if (window.innerWidth <= 1024) {
        navMenu.style.display = 'none';
        openMenuBtn.style.display = 'inline-block';
        closeMenuBtn.style.display = 'none';
        navMenu.classList.remove('open');
        // Closes mobile dropdown when closing the menu
        if (dropdownMenu) dropdownMenu.style.display = 'none';
    }
}

/* ==================================================
   WINDOW RESIZE HANDLER
   --------------------------------------------------
   Purpose: Resets navigation menu styles when the window is resized.
   Functionality: Clears inline styles for larger screens (>1024px) and resets mobile menu states for smaller screens.
   UI Role: Ensures consistent navigation behavior across screen sizes.
*/
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        navMenu.style.display = '';
        openMenuBtn.style.display = '';
        closeMenuBtn.style.display = '';
        if (dropdownMenu) dropdownMenu.style.display = '';
    } else {
        navMenu.classList.remove('open');
        if (dropdownMenu) dropdownMenu.style.display = 'none';
    }
});

/* ==================================================
   NAVIGATION EVENT LISTENERS
   --------------------------------------------------
   Purpose: Attaches event listeners for opening and closing the navigation menu.
   Functionality: Listens for clicks on menu buttons and links to toggle the menu, excluding dropdown parent links.
   UI Role: Enables user interaction with the navigation menu.
*/
openMenuBtn.addEventListener('click', openNav);
closeMenuBtn.addEventListener('click', closeNav);

// Closes menu when clicking on non-dropdown links
document.querySelectorAll('.nav_menu a').forEach(link => {
    // Skip dropdown parent links to prevent closing
    if (link.parentElement.classList.contains('dropdown')) return;
    link.addEventListener('click', closeNav);
});

/* ==================================================
   DROPDOWN TOGGLE (MOBILE)
   --------------------------------------------------
   Purpose: Toggles the dropdown menu visibility on mobile screens.
   Functionality: Prevents default link behavior on mobile (≤1024px) and toggles dropdown visibility; closes menu on dropdown item clicks.
   UI Role: Enhances mobile navigation with dropdown functionality.
*/
const dropdown = document.querySelector('.nav_menu .dropdown > a');
const dropdownMenu = document.querySelector('.nav_menu .dropdown-menu');
if (dropdown && dropdownMenu) {
    dropdown.addEventListener('click', function(e) {
        // Prevent default only on smaller screens
        if (window.innerWidth <= 1024) {
            e.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        }
    });
    // Closes mobile menu when clicking a dropdown item
    dropdownMenu.querySelectorAll('a').forEach(item => {
        item.addEventListener('click', closeNav);
    });
}

/* ==================================================
   WINDOW SCROLL EFFECT
   --------------------------------------------------
   Purpose: Adds a visual effect to the navbar when scrolling.
   Functionality: Toggles the 'window-scroll' class on the navbar when the scroll position exceeds 100px.
   UI Role: Enhances the navbar’s appearance with a shadow or background change on scroll.
*/
window.addEventListener('scroll', () => {
    nav.classList.toggle('window-scroll', window.scrollY > 100);
});

/* ==================================================
   SECTION ANIMATION ON SCROLL
   --------------------------------------------------
   Purpose: Animates sections when they enter the viewport.
   Functionality: Uses IntersectionObserver to add a 'fade-in' class to sections when they are 10% visible.
   UI Role: Creates a smooth entrance animation for sections to improve user engagement.
*/
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
        }
    });
}, observerOptions);

// Observes all sections for animation
document.querySelectorAll('section').forEach(section => {
    observer.observe(section);
});

/* ==================================================
   SUMMARY CARDS ANIMATION
   --------------------------------------------------
   Purpose: Animates numerical counters in summary cards when they enter the viewport.
   Functionality: Uses IntersectionObserver to trigger a smooth count-up animation for numbers in '.count' elements.
   UI Role: Provides a dynamic and engaging display of key metrics.
*/
const animateCount = (element, target, duration = 2000) => {
    let start = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(start);
        }
    }, 16);
};

const countObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const countElement = entry.target.querySelector('.count');
            const target = parseInt(countElement.textContent);
            animateCount(countElement, target);
            countObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.summary-card').forEach(card => {
    countObserver.observe(card);
});

/* ==================================================
   QUICK ACTION BUTTONS
   --------------------------------------------------
   Purpose: Adds a ripple effect to quick action buttons on click.
   Functionality: Dynamically creates a ripple span element at the click position, which animates and removes after 600ms.
   UI Role: Enhances button interactivity with a modern visual feedback effect.
*/
document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});


/* ==================================================
   SEARCH FUNCTIONALITY
   --------------------------------------------------
   Purpose: Adds a search bar to filter action cards dynamically.
   Functionality: Creates a search input and filters action cards based on title and description matches.
   UI Role: Enhances usability by allowing users to quickly find relevant actions.
*/
const addSearchFunctionality = () => {
    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.placeholder = 'Pesquisar ações...';
    searchInput.className = 'search-input';
    searchInput.style.cssText = `
        width: 100%;
        max-width: 300px;
        padding: 0.8rem 1rem;
        border: 2px solid var(--color-light);
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        font-family: inherit;
        transition: var(--transition);
    `;
    
    const actionsSection = document.querySelector('.dashboard-actions');
    if (actionsSection) {
        const title = actionsSection.querySelector('h2');
        title.parentNode.insertBefore(searchInput, title.nextSibling);
        
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const actionCards = document.querySelectorAll('.action-card');
            
            actionCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                } else {
                    card.style.opacity = '0.3';
                }
            });
        });
    }
};

// Initializes search functionality
addSearchFunctionality();

/* ==================================================
   THEME TOGGLE
   --------------------------------------------------
   Purpose: Toggles between light and dark themes for the dashboard.
   Functionality: Creates a theme toggle button, switches themes, and saves the preference in localStorage.
   UI Role: Enhances user experience with customizable theme options.
*/
const addThemeToggle = () => {
    const themeToggle = document.createElement('button');
    themeToggle.innerHTML = '<i class="uil uil-moon"></i>';
    themeToggle.className = 'theme-toggle';
    themeToggle.style.cssText = `
        position: fixed;
        top: 6rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--color-secondary);
        color: var(--color-white);
        border: none;
        cursor: pointer;
        z-index: 10;
        transition: var(--transition);
        box-shadow: var(--shadow);
    `;
    
    document.body.appendChild(themeToggle);
    
    let isDark = false;
    
    themeToggle.addEventListener('click', () => {
        isDark = !isDark;
        document.body.classList.toggle('dark-theme', isDark);
        themeToggle.innerHTML = isDark ? '<i class="uil uil-sun"></i>' : '<i class="uil uil-moon"></i>';
        
        // Saves theme preference
        localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
    });
    
    // Loads saved theme preference
    const savedTheme = localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        isDark = true;
        document.body.classList.add('dark-theme');
        themeToggle.innerHTML = '<i class="uil uil-sun"></i>';
    }
};

// Initializes theme toggle
addThemeToggle();

/* ==================================================
   NOTIFICATIONS
   --------------------------------------------------
   Purpose: Displays temporary notification messages with customizable types.
   Functionality: Creates a notification element with slide-in/out animations and removes it after 3 seconds.
   UI Role: Provides feedback for user actions or system events.
*/
const showNotification = (message, type = 'info') => {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 6rem;
        right: 2rem;
        padding: 1rem 2rem;
        background: var(--color-white);
        color: var(--color-primary);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-hover);
        z-index: 1000;
        transform: translateX(100%);
        transition: var(--transition);
        border-left: 4px solid var(--color-secondary);
    `;
    
    document.body.appendChild(notification);
    
    // Animates notification in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Removes notification after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
};

/* ==================================================
   KEYBOARD SHORTCUTS
   --------------------------------------------------
   Purpose: Adds keyboard shortcuts for common actions.
   Functionality: Listens for Ctrl+K (search focus), Escape (close menu), and Ctrl+T (theme toggle).
   UI Role: Improves accessibility and efficiency for keyboard users.
*/
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K to focus search input
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close navigation menu
    if (e.key === 'Escape') {
        closeNav();
    }
    
    // Ctrl/Cmd + T to toggle theme
    if ((e.ctrlKey || e.metaKey) && e.key === 't') {
        e.preventDefault();
        document.querySelector('.theme-toggle').click();
    }
});

/* ==================================================
   PERFORMANCE OPTIMIZATION
   --------------------------------------------------
   Purpose: Optimizes scroll event handling to reduce performance overhead.
   Functionality: Uses a debounce function to limit the frequency of scroll event executions.
   UI Role: Ensures smooth performance during scrolling.
*/
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// Optimized scroll handler for navbar
const optimizedScrollHandler = debounce(() => {
    nav.classList.toggle('window-scroll', window.scrollY > 100);
}, 10);

window.addEventListener('scroll', optimizedScrollHandler);

/* ==================================================
   ERROR HANDLING
   --------------------------------------------------
   Purpose: Captures and logs global JavaScript errors.
   Functionality: Logs errors to the console without displaying intrusive alerts.
   UI Role: Ensures errors are tracked for debugging without affecting user experience.
*/
window.addEventListener('error', (e) => {
            // Logs error to console
        console.error('Global error captured:', e.message, e);
});

/* ==================================================
   INITIALIZATION
   --------------------------------------------------
   Purpose: Initializes dashboard functionality when the DOM is fully loaded.
   Functionality: Adds a 'loaded' class to the body and shows a welcome notification on the dashboard page.
   UI Role: Ensures all features are ready and provides a welcoming user experience.
*/
document.addEventListener('DOMContentLoaded', () => {
    // Adds loading animation class
    document.body.classList.add('loaded');
    
    // Shows welcome notification on dashboard page
    if (window.location.pathname === '/admin/dashboard' || window.location.pathname.endsWith('/admin/dashboard')) {
        setTimeout(() => {
            showNotification('Welcome to the Admin Dashboard!', 'success');
        }, 1000);
    }
});

/* ==================================================
   UTILITY FUNCTIONS
   --------------------------------------------------
   Purpose: Provides reusable utility functions for formatting and time display.
   Functionality: Includes functions to format numbers with commas and get the current time.
   UI Role: Enhances data presentation and time-related features.
*/
// Formats numbers with commas
const formatNumber = (num) => {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

// Gets current time in a readable format
const getCurrentTime = () => {
    return new Date().toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

/* ==================================================
   EXPORTED FUNCTIONS
   --------------------------------------------------
   Purpose: Exposes utility functions for external use.
   Functionality: Makes notification, number formatting, and time functions available globally.
   UI Role: Allows other scripts to utilize dashboard utilities.
*/
window.AdminDashboard = {
    showNotification,
    formatNumber,
    getCurrentTime
};

/* ==================================================
   NOTIFICATION DELETION
   --------------------------------------------------
   Purpose: Handles deletion of notifications via an API call.
   Functionality: Sends a DELETE request to the server, updates the UI, and shows a success/error notification.
   UI Role: Enables admins to remove notifications with feedback.
*/
window.deleteNotification = function(id) {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    fetch(`/admin/api/notifications/${id}/delete`, { method: 'DELETE' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                // Removes notification from UI
                const item = document.querySelector(`.activity-item[data-notification-id="${id}"]`);
                if (item) item.remove();
                showNotification('Notification deleted!', 'success');
            } else {
                showNotification(result.message || 'Failed to delete notification', 'error');
            }
        })
        .catch(() => {
            showNotification('Error deleting notification', 'error');
        });
};