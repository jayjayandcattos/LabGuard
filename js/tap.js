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

.table-header, .table-row {
    display: grid;
    grid-template-columns: 80px 1fr 1fr 1fr 1fr;
    padding: 0.75rem;
    align-items: center;
}

.table-header {
    background-color: #f1f1f1;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
}

.table-row {
    border-bottom: 1px solid #ddd;
}

.table-row span img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.status-present {
    color: green;
    font-weight: bold;
}

.status-absent {
    color: red;
    font-weight: bold;
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
    const photoPath = user.photo ? `backend/uploads/${user.photo}` : 'assets/default.jpg';
    const fullName = `${user.lastname}, ${user.firstname}`;
    const statusClass = status === 'Present' ? 'status-present' : 'status-absent';
    
    newRow.innerHTML = `
        <span><img src="${photoPath}" alt="User photo"></span>
        <span>${fullName}</span>
        <span>${checkInTime || '-'}</span>
        <span>${checkOutTime || '-'}</span>
        <span class="${statusClass}">${status}</span>
    `;
    return newRow;
}


let professorActive = false;


rfidInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const rfid_tag = this.value;
        
        
        triggerScanAnimation();
        
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'rfid_tag=' + encodeURIComponent(rfid_tag) + (professorActive ? '&dismiss_class=true' : '')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sections = document.querySelectorAll('.section');
                const professorSection = sections[0];
                const studentSection = sections[1];
                
                if (data.user.role === 'professor') {
                    
                    if (professorActive && data.dismiss_class) {
                        
                        professorActive = false;
                        
                        
                        const newRow = createTableRow(
                            data.user, 
                            data.user.check_in_time || '-', 
                            data.time, 
                            'Absent'
                        );
                        
                        const profTableBody = professorSection.querySelector('.table-row');
                        if (profTableBody) {
                            profTableBody.parentNode.replaceChild(newRow, profTableBody);
                        }
                        
                        
                        if (data.students && data.students.length > 0) {
                            data.students.forEach((student, index) => {
                                const studentRow = createTableRow(
                                    student,
                                    student.check_in_time || '-',
                                    data.time,
                                    'Absent'
                                );
                                
                                const studentRows = studentSection.querySelectorAll('.table-row');
                                if (index < studentRows.length) {
                                    studentRows[index].parentNode.replaceChild(studentRow, studentRows[index]);
                                } else {
                                    const table = studentSection.querySelector('.table');
                                    table.appendChild(studentRow);
                                }
                            });
                        }
                    } else {
                        
                        professorActive = true;
                        
                        const newRow = createTableRow(
                            data.user,
                            data.time,
                            '-',
                            'Present'
                        );
                        
                        const profTableBody = professorSection.querySelector('.table-row');
                        if (profTableBody) {
                            profTableBody.parentNode.replaceChild(newRow, profTableBody);
                        } else {
                            const table = professorSection.querySelector('.table');
                            if (table) {
                                table.appendChild(newRow);
                            }
                        }
                    }
                } else if (data.user.role === 'student') {
                    
                    const status = data.status === 'check_in' ? 'Present' : 'Absent';
                    const checkInTime = data.status === 'check_in' ? data.time : data.user.check_in_time;
                    const checkOutTime = data.status === 'check_out' ? data.time : '-';
                    
                    const newRow = createTableRow(
                        data.user,
                        checkInTime,
                        checkOutTime,
                        status
                    );
                    
                    
                    const studentRows = studentSection.querySelectorAll('.table-row');
                    let found = false;
                    
                    for (let i = 0; i < studentRows.length; i++) {
                        const nameCell = studentRows[i].children[1];
                        if (nameCell && nameCell.textContent === `${data.user.lastname}, ${data.user.firstname}`) {
                            studentRows[i].parentNode.replaceChild(newRow, studentRows[i]);
                            found = true;
                            break;
                        }
                    }
                    
                    if (!found) {
                        const table = studentSection.querySelector('.table');
                        if (table) {
                            table.appendChild(newRow);
                        }
                    }
                }
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