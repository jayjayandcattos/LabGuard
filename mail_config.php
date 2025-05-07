<?php
// backend/mail_config.php
// Mail configuration settings

// PHPMailer SMTP settings
define('MAIL_HOST', 'smtp.gmail.com');       // SMTP server
define('MAIL_PORT', 587);                    // SMTP port (587 for TLS, 465 for SSL)
define('MAIL_USERNAME', 'jhonrey.loreno77@gmail.com'); // Your email address
define('MAIL_PASSWORD', 'vfuq kzcj jvwv rpzl');    // Email password or app password
define('MAIL_FROM', 'your-email@gmail.com');     // Sender email address
define('MAIL_FROM_NAME', 'LabGuard Attendance System'); // Sender name
define('MAIL_ENCRYPTION', 'tls');            // Encryption type (tls or ssl)

// Debug level (0-4)
// 0 = off, 1 = client messages, 2 = client and server messages
define('MAIL_DEBUG', 0);
?>
