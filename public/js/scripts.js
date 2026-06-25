/* ==================================================
   MOBILE MENU TOGGLE JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages the mobile navigation menu toggle functionality.
   Functionality: Toggles the visibility of the main navigation menu on mobile devices.
   UI Role: Provides a responsive navigation experience for mobile users.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', () => {
    /* ==================================================
       MOBILE MENU TOGGLE SETUP
       --------------------------------------------------
       Purpose: Initializes the mobile menu toggle behavior.
       Functionality: Toggles the main navigation menu and updates ARIA attributes on click.
       UI Role: Enables users to show/hide the navigation menu on mobile devices.
       Logic: Listens for clicks on the toggle button, updates ARIA state, and toggles the menu's active class.
    */
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    // Sets up click event listener for the mobile menu toggle
    mobileMenuToggle.addEventListener('click', () => {
        // Checks current ARIA expanded state
        const isExpanded = mobileMenuToggle.getAttribute('aria-expanded') === 'true';
        // Toggles ARIA expanded attribute
        mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);
        // Toggles the active class to show/hide the navigation menu
        mainNav.classList.toggle('active');
    });
});