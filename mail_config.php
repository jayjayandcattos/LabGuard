<?php
// Mail configuration — all values come from .env (see .env.example).
require_once __DIR__ . '/env_loader.php';

define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', (int) (getenv('MAIL_PORT') ?: 587));
define('MAIL_USERNAME', getenv('MAIL_USER') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') !== false ? getenv('MAIL_PASSWORD') : '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: MAIL_USERNAME);
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'LabGuard Attendance System');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_DEBUG', (int) (getenv('MAIL_DEBUG') ?: 0));
