/**
 * Student ID Format Validation Script
 * This script enforces the student ID format of 00-0000
 * It prevents input beyond the correct format and automatically adds the dash
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all student ID inputs
    const studentIdInputs = document.querySelectorAll('input[name="student_id"]');
    
    studentIdInputs.forEach(input => {
        // Add event listeners for input validation
        input.addEventListener('input', formatStudentId);
        input.setAttribute('maxlength', 7); // 00-0000 format has 7 characters
        input.setAttribute('placeholder', '00-0000'); // Show the expected format
    });
    
    /**
     * Format and validate student ID input
     * Forces the format 00-0000 by:
     * 1. Adding a dash automatically after the first 2 digits
     * 2. Limiting to exactly 7 characters (00-0000)
     * 3. Only allowing digits and one dash
     */
    function formatStudentId(e) {
        let input = e.target;
        let value = input.value.replace(/[^\d-]/g, ''); // Remove non-digit and non-dash characters
        
        // Handle the automatic dash insertion
        if (value.length === 2 && !value.includes('-')) {
            value += '-';
        } else if (value.length > 2 && !value.includes('-')) {
            // Insert dash if missing
            value = value.substring(0, 2) + '-' + value.substring(2);
        }
        
        // Ensure only one dash at position 2
        if (value.includes('-') && value.indexOf('-') !== 2) {
            const parts = value.split('-');
            const digits = parts.join('');
            if (digits.length >= 2) {
                value = digits.substring(0, 2) + '-' + digits.substring(2);
            } else {
                value = digits;
            }
        }
        
        // Limit to 7 characters (00-0000 format)
        if (value.length > 7) {
            value = value.substring(0, 7);
        }
        
        // Update the input value
        input.value = value;
    }
}); 