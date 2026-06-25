/* ==================================================
   MY INTERESTS PAGE JAVASCRIPT
   --------------------------------------------------
   Purpose: Manages the display of a modal with module leader details for the My Interests page.
   Functionality: Shows/hides a modal with leader information and closes it when clicking outside.
   UI Role: Provides an interactive modal for viewing module leader details associated with user interests.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

/* ==================================================
   GLOBAL DATA
   --------------------------------------------------
   Purpose: Stores interest data for the page.
   Functionality: Initializes an array of interests from server-injected data.
   UI Role: Provides access to interest data for displaying leader details.
   Logic: Retrieves interest data from a global variable set by the Twig template.
*/
let interests = window.myInterestsData || []; // Stores interest data injected by Twig template

/* ==================================================
   CLOSE LEADER MODAL
   --------------------------------------------------
   Purpose: Hides the module leader details modal.
   Functionality: Sets the modal's display style to 'none'.
   UI Role: Allows users to close the modal when done viewing leader details.
   Logic: Updates the modal's CSS display property to hide it.
*/
function closeLeaderModal() {
    // Hides the leader modal
    document.getElementById('leader-modal').style.display = 'none';
}

/* ==================================================
   SHOW LEADER MODAL
   --------------------------------------------------
   Purpose: Displays a modal with module leader details for a selected interest.
   Functionality: Populates modal fields with leader data and shows the modal.
   UI Role: Presents module leader information in a user-friendly modal.
   Logic: Retrieves leader data from the interests array, updates modal content, and sets display to 'flex'.
*/
function showLeaderModal(index) {
    // Retrieves leader data for the specified interest
    const leader = window.myInterestsData[index];
    if (!leader) return; // Exits if no leader data is found

    // Populates modal fields with leader details, using fallback values
    document.getElementById('leader-name').textContent = leader.leader_name || 'N/A';
    document.getElementById('leader-email').textContent = leader.leader_email || 'N/A';
    document.getElementById('leader-department').textContent = leader.leader_department || 'N/A';
    document.getElementById('leader-office').textContent = leader.leader_office_location || 'N/A';

    // Shows the modal
    document.getElementById('leader-modal').style.display = 'flex';
}

/* ==================================================
   MODAL CLOSE ON OUTSIDE CLICK
   --------------------------------------------------
   Purpose: Closes the modal when clicking outside its content.
   Functionality: Hides the modal if the click target is the modal background.
   UI Role: Enhances usability by allowing modal dismissal via background click.
   Logic: Checks if the click target is the modal container and calls closeLeaderModal.
*/
window.onclick = function (event) {
    const modal = document.getElementById('leader-modal');
    // Closes modal if the click is on the modal background
    if (event.target === modal) {
        closeLeaderModal();
    }
};