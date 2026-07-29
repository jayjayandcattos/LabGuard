const rfidInput = document.createElement('input');
rfidInput.type = 'text';
rfidInput.id = 'rfid_input';
rfidInput.style.position = 'absolute';
rfidInput.style.opacity = '0';
rfidInput.style.pointerEvents = 'none';
document.body.appendChild(rfidInput);


let captureRfidInput = true;
let isInModal = false;


function isElementInModal(element) {
    return element.closest('.loginmodal') !== null || 
           element.closest('.aboutmodal') !== null || 
           element.closest('form') !== null;
}


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


document.addEventListener('mousedown', function(event) {
    
    if (!isElementInModal(event.target)) {
        captureRfidInput = true;
    }
});


document.addEventListener('DOMContentLoaded', function() {
    
    const loginBtn = document.getElementById('loginBtn');
    const loginModal = document.getElementById('loginModal');
    
    if (loginBtn && loginModal) {
        loginBtn.addEventListener('click', function() {
            isInModal = true;
            captureRfidInput = false;
        });
    }
    
    
    const aboutBtn = document.getElementById('aboutBtn');
    const aboutModal = document.getElementById('aboutModal');
    
    if (aboutBtn && aboutModal) {
        aboutBtn.addEventListener('click', function() {
            isInModal = true;
            captureRfidInput = false;
        });
    }
    
    
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


const style = document.createElement('style');
style.textContent = `
.scan-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-top: 2.5rem;
}

.scan-container::before {
    content: '';
    position: absolute;
    top: 30%;
    left: 50%;
    width: 400px;
    height: 400px;
    border: 12px solid rgba(255, 255, 255, 0.3);
    border-top: 12px solid rgb(109, 219, 246);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: spin 1s linear infinite;
    opacity: 1;
    transition: all 0.3s ease-in-out;
    cursor: pointer;
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
    content: '';
    position: absolute;
    top: 30%;
    left: 50%;
    width: 40px; 
    height: 80px; 
    border: solid rgb(26, 136, 26);
    border-width: 0 10px 10px 0; 
    transform: translate(-50%, -50%) rotate(45deg); 
    animation: checkmarkConfirmation 0.5s ease-in-out forwards;
    opacity: 0; 
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

.table-header, .table-row {
    display: grid;
    grid-template-columns: 80px 1.5fr 1fr 1fr 1fr;
    padding: 0.75rem;
    align-items: center;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.5rem;
    border-radius: 8px;
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
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-present {
    color: #22c55e;
    font-weight: bold;
}

.status-absent {
    color: #ef4444;
    font-weight: bold;
}

.section {
    margin-bottom: 2rem;
}
`;
document.head.appendChild(style);


function triggerScanAnimation(success = true) {
    const scanContainer = document.querySelector('.scan-container');
    if (!scanContainer) return;
    
    
    const scanHeading = scanContainer.querySelector('h2');
    const originalText = scanHeading ? scanHeading.textContent : "PLEASE SCAN YOUR ID.";
    
    if (scanHeading) {
        
        scanHeading.style.minWidth = scanHeading.offsetWidth + 'px';
        scanHeading.style.textAlign = 'center';
        scanHeading.textContent = "SCANNING...";
    }
    
    scanContainer.classList.add('scanning');
    
    setTimeout(() => {
        scanContainer.classList.remove('scanning');
        if (success) {
            scanContainer.classList.add('confirmed');
            setTimeout(() => {
                scanContainer.classList.remove('confirmed');
                
                if (scanHeading) {
                    scanHeading.textContent = originalText;
                    
                    setTimeout(() => {
                        scanHeading.style.minWidth = '';
                    }, 100);
                }
            }, 1000);
        } else {
            
            if (scanHeading) {
                scanHeading.textContent = originalText;
                
                setTimeout(() => {
                    scanHeading.style.minWidth = '';
                }, 100);
            }
        }
    }, 1500);
}

function createTableRow(user, checkInTime, checkOutTime, status) {
    const newRow = document.createElement('div');
    newRow.className = 'table-row';
    
    const userId = user.role === 'professor' ? user.prof_user_id : user.student_user_id;
    if (!userId) {
        console.error('No user ID found for user:', user);
        return newRow;
    }
    
    newRow.setAttribute('data-id', userId);
    
    // Enhanced image path handling
    let photoPath;
    if (!user.photo) {
        photoPath = 'assets/IDtap.svg';
    } else {
        photoPath = user.photo.startsWith('uploads/') ? user.photo : `uploads/${user.photo}`;
    }
    
    const fullName = `${user.lastname}, ${user.firstname}`;
    const statusText = status === 'check_out' ? 'Absent' : 'Present';
    const statusClass = status === 'check_out' ? 'status-absent' : 'status-present';
    
    newRow.innerHTML = `
        <span><img src="${photoPath}" alt="${fullName}" onerror="this.src='assets/IDtap.svg'"></span>
        <span>${fullName}</span>
        <span>${checkInTime || '-'}</span>
        <span>${checkOutTime || '-'}</span>
        <span class="${statusClass}">${statusText}</span>
    `;
    
    return newRow;
}


let professorActive = false;


rfidInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const rfid_tag = this.value;
        console.clear();
        console.log('Scanning card:', rfid_tag);
        
        triggerScanAnimation();
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'rfid_tag=' + encodeURIComponent(rfid_tag)
        })
        .then(async response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new TypeError("Expected JSON response but got " + contentType);
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            
            if (data.success) {
                // Get the appropriate section based on user role
                const section = data.user.role === 'professor' ? 
                    document.querySelector('.section:first-child') : 
                    document.querySelector('.section:last-child');
                
                if (!section) {
                    console.error('Could not find appropriate section for role:', data.user.role);
                    return;
                }
                
                const table = section.querySelector('.table');
                if (!table) {
                    console.error('Could not find table in section');
                    return;
                }
                
                // Create new row
                const newRow = document.createElement('div');
                newRow.className = 'table-row';
                
                // Handle photo path
                let photoPath = data.user.photo ? 
                    (data.user.photo.startsWith('uploads/') ? data.user.photo : `uploads/${data.user.photo}`) : 
                    'assets/IDtap.svg';
                
                // Create row content
                newRow.innerHTML = `
                    <span><img src="${photoPath}" alt="User Photo" onerror="this.src='assets/IDtap.svg'"></span>
                    <span>${data.user.lastname}, ${data.user.firstname}</span>
                    <span>${data.time}</span>
                    <span>${data.status === 'check_out' ? data.time : '-'}</span>
                    <span class="${data.status === 'check_in' ? 'status-present' : 'status-absent'}">
                        ${data.status === 'check_in' ? 'Present' : 'Absent'}
                    </span>
                `;
                
                // Insert at the top of the table
                const header = table.querySelector('.table-header');
                if (header) {
                    table.insertBefore(newRow, header.nextSibling);
                } else {
                    table.appendChild(newRow);
                }
                
                // Handle mass student check-outs if professor is checking out
                if (data.user.role === 'professor' && 
                    data.status === 'check_out' && 
                    data.checked_out_students && 
                    data.checked_out_students.length > 0) {
                    
                    const studentTable = document.querySelector('.section:last-child .table');
                    if (studentTable) {
                        data.checked_out_students.forEach(studentData => {
                            const studentRow = document.createElement('div');
                            studentRow.className = 'table-row';
                            
                            let studentPhotoPath = studentData.user.photo ? 
                                (studentData.user.photo.startsWith('uploads/') ? studentData.user.photo : `uploads/${studentData.user.photo}`) : 
                                'assets/IDtap.svg';
                            
                            studentRow.innerHTML = `
                                <span><img src="${studentPhotoPath}" alt="Student Photo" onerror="this.src='assets/IDtap.svg'"></span>
                                <span>${studentData.user.lastname}, ${studentData.user.firstname}</span>
                                <span>${studentData.check_in_time || '-'}</span>
                                <span>${data.time}</span>
                                <span class="status-absent">Absent</span>
                            `;
                            
                            const studentHeader = studentTable.querySelector('.table-header');
                            if (studentHeader) {
                                studentTable.insertBefore(studentRow, studentHeader.nextSibling);
                            } else {
                                studentTable.appendChild(studentRow);
                            }
                        });
                    }
                }
                
                // Clean up old entries in both sections
                document.querySelectorAll('.section .table').forEach(table => {
                    const rows = Array.from(table.querySelectorAll('.table-row:not(.table-header)'));
                    while (rows.length > 5) {
                        const lastRow = rows.pop();
                        if (lastRow && lastRow.parentNode) {
                            lastRow.parentNode.removeChild(lastRow);
                        }
                    }
                });
                
            } else {
                console.error('Error:', data.message);
                triggerScanAnimation(false);
            }
            
            this.value = '';
        })
        .catch(error => {
            console.error('Error:', error);
            triggerScanAnimation(false);
            this.value = '';
        });
    }
});


window.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('.section');
    
    sections.forEach(section => {
        const table = section.querySelector('.table');
        if (table && !table.querySelector('.table-header')) {
            const header = document.createElement('div');
            header.className = 'table-header';
            header.innerHTML = `
                <span>PHOTO</span>
                <span>NAME</span>
                <span>CHECK IN</span>
                <span>CHECK OUT</span>
                <span>STATUS</span>
            `;
            table.insertBefore(header, table.firstChild);
        }
    });
    
    
    if (document.querySelector('.scan-container')) {
        rfidInput.focus();
        captureRfidInput = true;
    } else {
        captureRfidInput = false;
    }
});


setInterval(() => {
    
    if (captureRfidInput && 
        document.activeElement !== rfidInput && 
        document.querySelector('.scan-container') && 
        !isInModal) {
        rfidInput.focus();
    }
}, 1000);