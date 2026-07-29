// Active Class Functionality
console.log("LabGuard activeClass.js - Loaded");

// Get the active class container element
const activeClassContainer = document.getElementById('active-class-container');
const activeSubject = document.getElementById('active-subject');
const activeProfessor = document.getElementById('active-professor');
const activeRoom = document.getElementById('active-room');
const activeTime = document.getElementById('active-time');

/**
 * Fetch active class data from the server
 */
function fetchActiveClass() {
    console.log('Fetching active class data...');
    fetch('backend/get_active_class.php')
        .then(response => response.json())
        .then(data => {
            console.log('Active class data:', data);
            const container = document.getElementById('active-class-container');
            
            if (data.status === 'success' && data.active === true && data.data) {
                // Only display if there's a legitimate active class (not no_schedule)
                if (data.data.professor && !data.data.professor.includes('(Unscheduled)')) {
                    // Update the active class display with the class information
                    document.getElementById('active-subject').textContent = 
                        `${data.data.subject_code} - ${data.data.subject_name}`;
                    document.getElementById('active-professor').textContent = data.data.professor;
                    document.getElementById('active-room').textContent = data.data.room;
                    document.getElementById('active-time').textContent = data.data.time;
                    
                    // Show the active class container
                    container.style.display = 'block';
                } else {
                    // Hide for unscheduled classes
                    container.style.display = 'none';
                }
            } else {
                // No active class or error - hide the container
                container.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching active class:', error);
            document.getElementById('active-class-container').style.display = 'none';
        });
}

// Initial fetch when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing active class display - will only show when professor is present and class has started');
    fetchActiveClass();
    
    // Refresh active class data every 30 seconds
    setInterval(fetchActiveClass, 30000);
    
    // Make the function available globally so it can be called from tap.js
    window.fetchActiveClass = fetchActiveClass;
});

// Listen for tap events to refresh active class data
document.addEventListener('tap-processed', function() {
    fetchActiveClass();
}); 