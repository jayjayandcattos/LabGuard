// Version indicator - new version
console.log("LabGuard tap.js - New version loaded");

// Global error handler
window.addEventListener('error', function(event) {
    console.error('JS Error Caught:', event.error);
});

// RFID Input field setup
const rfidInput = document.createElement('input');
rfidInput.type = 'text';
rfidInput.id = 'rfid_input';
rfidInput.style.position = 'absolute';
rfidInput.style.opacity = '0';
rfidInput.style.pointerEvents = 'none';
document.body.appendChild(rfidInput);

// Focus management variables
let captureRfidInput = true;
let isInModal = false;

// Helper function to check if an element is inside a modal
function isElementInModal(element) {
    return element.closest('.loginmodal') !== null || 
           element.closest('.aboutmodal') !== null || 
           element.closest('form') !== null;
}

// Click handling to manage input focus
document.addEventListener('click', function(event) {
    if (isElementInModal(event.target) || 
        event.target.tagName === 'BUTTON' || 
        event.target.tagName === 'INPUT' || 
        event.target.tagName === 'SELECT' ||
        event.target.tagName === 'A' ||
        event.target.classList.contains('nav-option')) {
        captureRfidInput = false;
        return;
    }
    
    if (captureRfidInput) {
        rfidInput.focus();
    }
});

// Mouse down handler to reset capture state
document.addEventListener('mousedown', function(event) {
    if (!isElementInModal(event.target)) {
        captureRfidInput = true;
    }
});

// Modal handling on page load
document.addEventListener('DOMContentLoaded', function() {
    // Login modal handling
    const loginBtn = document.getElementById('loginBtn');
    const loginModal = document.getElementById('loginModal');
    
    if (loginBtn && loginModal) {
        loginBtn.addEventListener('click', function() {
            isInModal = true;
            captureRfidInput = false;
        });
    }
    
    // About modal handling
    const aboutBtn = document.getElementById('aboutBtn');
    const aboutModal = document.getElementById('aboutModal');
    
    if (aboutBtn && aboutModal) {
        aboutBtn.addEventListener('click', function() {
            isInModal = true;
            captureRfidInput = false;
        });
    }
    
    // Close button handling for modals
    const closeButtons = document.querySelectorAll('.close, .modal-overlay');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            isInModal = false;
            
            if (document.querySelector('.scan-container')) {
                captureRfidInput = true;
                rfidInput.focus();
            }
        });
    });
});

// Add CSS for scan animation and tables
const style = document.createElement('style');
style.textContent = `
.scan-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-top: 2.5rem;
    /* Add these properties to maintain position */
    width: 100%;
    min-height: 400px; /* Ensure consistent height */
}


.scan-container::before,
.scan-container::after {
    content: '';
    position: absolute;
    top: 30%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.scan-container::before {
    width: 400px;
    height: 400px;
    border: 12px solid rgba(255, 255, 255, 0.3);
    border-top: 12px solid rgb(109, 219, 246);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    opacity: 1;
    transition: all 0.3s ease-in-out;
    cursor: pointer;
    z-index: 1;
}

.scan-container.scanning::before {
    border: 12px solid rgba(0, 255, 0, 0.3);
    border-top: 12px solid rgb(0, 255, 0);
    animation: spin 1s linear infinite;
}

.scan-container.confirmed::before {
    border-color: rgb(0, 255, 0);
    box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
    animation: none; 
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.scan-container.confirmed::after {
    width: 40px; 
    height: 80px; 
    border: solid rgb(26, 136, 26);
    border-width: 0 10px 10px 0; 
    transform: translate(-50%, -50%) rotate(45deg); 
    animation: checkmarkConfirmation 0.5s ease-in-out forwards;
    opacity: 0;
    z-index: 2;
    /* Make sure this stays centered */
    top: 30%;
    left: 50%;
}

@keyframes spin {
    0% {
        transform: translate(-50%, -50%) rotate(0deg);
    }
    100% {
        transform: translate(-50%, -50%) rotate(360deg);
    }
}

@keyframes checkmarkConfirmation {
    0% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.8) rotate(45deg);
    }
    50% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.1) rotate(45deg);
    }
    100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1) rotate(45deg);
    }
}
    

// asdasfsafa

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.table h1 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: white;
}

.table-header {
    overflow: auto;  
    display: grid;
    grid-template-columns: 80px 1.2fr 1fr 0.8fr 0.8fr 0.8fr;
    padding: 0.75rem;
    align-items: center;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.5rem;
    border-radius: 8px;
    max-width: 100%;  // Ensure it doesn't exceed container width
}

.table-row {  
    display: grid;
    grid-template-columns: 80px 1.2fr 1fr 0.8fr 0.8fr 0.8fr;
    padding: 0.75rem;
    align-items: center;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.5rem;
    border-radius: 8px;
    max-height: 60px; /* increased height for photo */
}

.table-body {
    max-height: 350px; /* increased height */
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    width: 100%;
}

.table-header {
    background-color: rgba(241, 241, 241, 0.95);
    font-weight: bold;
}

.table-row {
    transition: all 0.3s ease;
}

.table-row:hover {
    transform: translateX(5px);
}

.table-row span img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Status styles */
.status-present {
    color: #28a745;
    font-weight: bold;
}

.status-absent {
    color: #dc3545;
    font-weight: bold;
}

.status-late {
    color: #ffc107;
    font-weight: bold;
}

.status-ended {
    color: #6c757d;
    font-weight: bold;
}

.status-no-schedule {
    color: #17a2b8;
    font-weight: bold;
}

.status-wrong-class {
    color: #e83e8c;
    font-weight: bold;
}
`;

document.head.appendChild(style);

// Function to format user photo path
function formatPhotoPath(photo) {
    return photo && photo.includes('/') ? photo : `backend/uploads/${photo}`;
}

// Animation functions for scan feedback
function triggerScanAnimation(success = true) {
    const scanContainer = document.querySelector('.scan-container');
    const scanImage = scanContainer ? scanContainer.querySelector('img') : null;
    
    if (success) {
        setTimeout(() => {
            scanContainer.classList.remove('scanning');
            scanContainer.classList.add('confirmed');
            
            if (scanImage) scanImage.style.opacity = '0';
            
            setTimeout(() => {
                scanContainer.classList.remove('confirmed');
                if (scanImage) scanImage.style.opacity = '1';
            }, 2000);
        }, 500);
    } else {
        scanContainer.classList.add('error');
        
        if (scanImage) scanImage.style.opacity = '0.5';
        
        setTimeout(() => {
            scanContainer.classList.remove('error');
            if (scanImage) scanImage.style.opacity = '1';
        }, 2000);
    }
}

// Process RFID input
rfidInput.addEventListener('change', function() {
    if (!this.value) return;
    
    // Show animation to indicate scanning
    const scanContainer = document.querySelector('.scan-container');
    const scanImage = scanContainer ? scanContainer.querySelector('img') : null;
    
    scanContainer.classList.add('scanning');
    if (scanImage) scanImage.classList.add('scanning');
    
    // Process the RFID tag through the server
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'rfid_tag=' + encodeURIComponent(this.value)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response data:', data);
        console.log('User data:', data.user);
        console.log('Status:', data.status);
        console.log('a_status:', data.a_status);
        console.log('Success:', data.success);
        
        // Function to display error message as a pop-up
        function showErrorPopup(message) {
            // Create modal container
            const modalContainer = document.createElement('div');
            modalContainer.className = 'error-modal-container';
            modalContainer.style.position = 'fixed';
            modalContainer.style.top = '0';
            modalContainer.style.left = '0';
            modalContainer.style.width = '100%';
            modalContainer.style.height = '100%';
            modalContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            modalContainer.style.display = 'flex';
            modalContainer.style.justifyContent = 'center';
            modalContainer.style.alignItems = 'center';
            modalContainer.style.zIndex = '1000';
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.className = 'error-modal-content';
            modalContent.style.backgroundColor = '#1e3a8a';
            modalContent.style.color = 'white';
            modalContent.style.padding = '20px';
            modalContent.style.borderRadius = '10px';
            modalContent.style.boxShadow = '0 0 20px rgba(255, 255, 255, 0.3)';
            modalContent.style.maxWidth = '80%';
            modalContent.style.textAlign = 'center';
            modalContent.style.fontFamily = "'Orbitron', sans-serif";
            
            // Add error icon
            const errorIcon = document.createElement('div');
            errorIcon.innerHTML = '⚠️';
            errorIcon.style.fontSize = '48px';
            errorIcon.style.marginBottom = '10px';
            
            // Add error message
            const errorMessage = document.createElement('div');
            errorMessage.textContent = message;
            errorMessage.style.fontSize = '18px';
            errorMessage.style.marginBottom = '20px';
            
            // Add close button
            const closeButton = document.createElement('button');
            closeButton.textContent = 'OK';
            closeButton.style.backgroundColor = '#3b82f6';
            closeButton.style.color = 'white';
            closeButton.style.border = 'none';
            closeButton.style.padding = '10px 20px';
            closeButton.style.borderRadius = '5px';
            closeButton.style.cursor = 'pointer';
            closeButton.style.fontFamily = "'Orbitron', sans-serif";
            closeButton.style.fontWeight = 'bold';
            
            // Handle close button click
            closeButton.addEventListener('click', function() {
                document.body.removeChild(modalContainer);
            });
            
            // Add auto-close after 5 seconds
            setTimeout(() => {
                if (document.body.contains(modalContainer)) {
                    document.body.removeChild(modalContainer);
                }
            }, 5000);
            
            // Assemble modal
            modalContent.appendChild(errorIcon);
            modalContent.appendChild(errorMessage);
            modalContent.appendChild(closeButton);
            modalContainer.appendChild(modalContent);
            
            // Add to body
            document.body.appendChild(modalContainer);
        }
        
        // Always show the animation result based on success status
        triggerScanAnimation(data.success);
        
        // Show error message as popup if not successful
        if (!data.success && data.message) {
            console.log('Showing error popup:', data.message);
            showErrorPopup(data.message);
        }
        
        // Create a function to add a row to the table
        function addRowToTable(userData, timeIn, timeOut, status, aStatus, room) {
            const targetTable = userData.role === 'professor' 
                ? document.querySelector('.section:nth-child(1) .table')
                : document.querySelector('.section:nth-child(2) .table');
                
            if (!targetTable) {
                console.error('Target table not found for role:', userData.role);
                return;
            }
            
            // Check and update headers if needed
            const header = targetTable.querySelector('.table-header');
            if (header) {
                // Make sure header has the room column
                if (header.children.length === 5) {
                    // Old header, need to update it
                    header.innerHTML = `
                        <span>PHOTO</span>
                        <span>NAME</span>
                        <span>ROOM</span>
                        <span>CHECK IN</span>
                        <span>CHECK OUT</span>
                        <span>STATUS</span>
                    `;
                }
            }
            
            // Determine status class based on status
            let statusClass = '';
            let displayStatus = aStatus || 'No Status';
            
            switch(status) {
                case 'check_in':
                    statusClass = aStatus === 'Late' ? 'status-late' : 'status-present';
                    break;
                case 'check_out':
                    statusClass = 'status-ended';
                    break;
                case 'no_schedule':
                    statusClass = 'status-no-schedule';
                    displayStatus = 'No Schedule';
                    break;
                case 'wrong_class':
                    statusClass = 'status-wrong-class';
                    break;
                default:
                    // Check aStatus directly as a fallback
                    if (aStatus === 'No Schedule') {
                        statusClass = 'status-no-schedule';
                        displayStatus = 'No Schedule';
                    } else {
                        statusClass = 'status-no-schedule';
                    }
            }
            
            // Format room display
            let roomDisplay = room && room.room_number ? `Room ${room.room_number}` : '-';
            
            // Use formatted photo path
            let photoPath = formatPhotoPath(userData.photo);
            
            // Generate unique user identifier
            const userId = `${userData.role}-${userData.id || userData.lastname + userData.firstname}`;
            
            // Get the table body
            const tableBody = targetTable.querySelector('.table-body');
            if (!tableBody) {
                console.log('Table body not found');
                return;
            }
            
            // Check if this user already has a row
            let existingRow = null;
            const rows = Array.from(tableBody.querySelectorAll('.table-row'));
            
            for (const row of rows) {
                if (row.dataset.userId === userId) {
                    existingRow = row;
                    break;
                }
            }
            
            if (existingRow) {
                // Update existing row
                console.log('Updating existing row for user:', userId);
                
                // Update room information if it has changed
                const roomSpan = existingRow.querySelectorAll('span')[2];
                if (roomSpan.textContent !== roomDisplay) {
                    roomSpan.textContent = roomDisplay;
                }
                
                // Update check-out time and status if provided
                if (timeOut) {
                    existingRow.querySelectorAll('span')[4].textContent = timeOut;
                }
                
                // Always update the status to the latest one
                const statusSpan = existingRow.querySelectorAll('span')[5];
                statusSpan.className = statusClass;
                statusSpan.textContent = displayStatus;
                
                // Move the row to the top (most recent)
                if (tableBody.firstChild !== existingRow) {
                    tableBody.insertBefore(existingRow, tableBody.firstChild);
                }
            } else {
                // Create new row
                const newRow = document.createElement('div');
                newRow.className = 'table-row';
                newRow.dataset.userId = userId;
                
                // Set content
                newRow.innerHTML = `
                    <span><img src="${photoPath}" alt="User Photo" onerror="this.src='assets/IDtap.svg'"></span>
                    <span>${userData.lastname}, ${userData.firstname}</span>
                    <span>${roomDisplay}</span>
                    <span>${timeIn}</span>
                    <span>${timeOut || '-'}</span>
                    <span class="${statusClass}">
                        ${displayStatus}
                    </span>
                `;
                
                // Insert at top
                if (tableBody.firstChild) {
                    tableBody.insertBefore(newRow, tableBody.firstChild);
                } else {
                    tableBody.appendChild(newRow);
                }
                console.log('New row added to table body for user:', userId);
            }
            
            // Cleanup to max 5 rows
            const updatedRows = Array.from(tableBody.querySelectorAll('.table-row'));
            if (updatedRows.length > 5) {
                for (let i = 5; i < updatedRows.length; i++) {
                    if (updatedRows[i] && updatedRows[i].parentNode) {
                        updatedRows[i].parentNode.removeChild(updatedRows[i]);
                    }
                }
            }
        }
        
        // Handle user photo display in scan container
        if (data.user && scanContainer && data.user.photo) {
            // Get proper photo path
            let photoPath = formatPhotoPath(data.user.photo);
            
            // Remove previous photo if exists
            const oldPhoto = scanContainer.querySelector('.user-photo');
            if (oldPhoto) {
                oldPhoto.remove();
            }
            
            // Create and add new photo
            const photoDiv = document.createElement('div');
            photoDiv.className = 'user-photo';
            photoDiv.innerHTML = `<img src="${photoPath}" alt="${data.user.firstname} ${data.user.lastname}" 
                                  onerror="this.src='assets/IDtap.svg'" 
                                  style="width:120px; height:120px; border-radius:50%; object-fit:cover; 
                                  border:3px solid #fff; box-shadow:0 4px 8px rgba(0,0,0,0.2); 
                                  position:absolute; top:-160px; left:50%; transform:translateX(-50%); 
                                  background-color:#fff; z-index:100;">`;
            
            scanContainer.appendChild(photoDiv);
            
            // Remove photo after 3 seconds
            setTimeout(() => {
                if (photoDiv.parentNode) {
                    photoDiv.remove();
                }
            }, 3000);
        }
        
        // Process data and update tables
        if (data.user) {
            if (data.success) {
                // Normal successful tap
                addRowToTable(
                    data.user, 
                    data.time, 
                    data.status === 'check_out' ? data.time : null, 
                    data.status, 
                    data.a_status,
                    data.room
                );
                
                // Immediately refresh active class display if professor either checks in or out
                if (data.user.role === 'professor') {
                    console.log('Professor attendance recorded, refreshing active class display');
                    window.fetchActiveClass && window.fetchActiveClass();
                }
            } else if (data.status === 'wrong_class') {
                // Wrong class case - explicitly handle
                console.log('Processing wrong_class status');
                addRowToTable(
                    data.user,
                    data.time,
                    null,
                    'wrong_class',
                    'Wrong Class',
                    data.room
                );
            } else {
                // Other error with user data
                console.error('Server error with user data:', data.message);
            }
        } else {
            // No user data available
            console.error('Server error, no user data:', data.message);
        }
        
        this.value = '';
        
        // Dispatch custom event for tap processing completion
        document.dispatchEvent(new CustomEvent('tap-processed', { 
            detail: { 
                success: data.success, 
                status: data.status || null 
            } 
        }));
    })
    .catch(error => {
        console.error('Fetch error:', error);
        triggerScanAnimation(false);
        this.value = '';
        
        // Dispatch custom event even on error
        document.dispatchEvent(new CustomEvent('tap-processed', { 
            detail: { 
                success: false,
                error: error.message
            } 
        }));
    });
});

// Re-focus the input after a short delay
setInterval(() => {
    if (captureRfidInput && !isInModal) {
        rfidInput.focus();
    }
}, 200);

// Periodically update professor statuses for ended classes
function checkEndedClasses() {
    // Make AJAX call to check for ended classes
    fetch('backend/update_ended_classes.php')
    .then(response => response.json())
    .then(data => {
        console.log('Updated ended classes check:', data);
        
        // If professors were updated, refresh UI
        if (data.status === 'success' && data.updated_professors && data.updated_professors.length > 0) {
            console.log('Professors with ended classes updated:', data.updated_professors.length);
            
            // Refresh professor tap history to show updated statuses
            const professorTapHistory = document.querySelector('.section:first-child .table-body');
            
            if (professorTapHistory) {
                // Update any rows with updated professors to show "No Schedule" status
                data.updated_professors.forEach(prof => {
                    const rows = professorTapHistory.querySelectorAll('.table-row');
                    console.log(`Looking for professor ${prof.lastname} among ${rows.length} rows`);
                    
                    for (const row of rows) {
                        // Try to match the professor name from the row text content
                        const nameCell = row.querySelector('span:nth-child(2)');
                        if (nameCell && nameCell.textContent.includes(prof.lastname)) {
                            console.log(`Found professor ${prof.lastname}, updating status to No Schedule`);
                            // Update status
                            const statusCell = row.querySelector('span:nth-child(6)');
                            if (statusCell) {
                                statusCell.className = 'status-no-schedule';
                                statusCell.textContent = 'No Schedule';
                                console.log('Status updated successfully');
                            }
                            break;
                        }
                    }
                });
            }
            
            // Refresh active class display if needed
            window.fetchActiveClass && window.fetchActiveClass();
        }
    })
    .catch(error => {
        console.error('Error checking ended classes:', error);
    });
}

// Run the check every 30 seconds
setInterval(checkEndedClasses, 30000);

// Initial checks after page load - run multiple checks in the beginning
setTimeout(checkEndedClasses, 1000);  // First check after 1 second
setTimeout(checkEndedClasses, 5000);  // Second check after 5 seconds
setTimeout(checkEndedClasses, 15000); // Third check after 15 seconds

// Initial focus
if (document.querySelector('.scan-container')) {
    setTimeout(() => {
        rfidInput.focus();
    }, 800);
}

// Listen for tap events to run status updates
document.addEventListener('tap-processed', function(event) {
    console.log('Tap processed, checking for ended classes...');
    checkEndedClasses();
});