<?php
/**
 * System Configuration
 * Professional Payroll Management System
 */

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Timezone
date_default_timezone_set('Asia/Manila');

// Base URL
define('BASE_URL', 'http://localhost/payrolll/');
define('BASE_PATH', __DIR__ . '/../');

// Application Settings
define('APP_NAME', 'Payroll Management System');
define('APP_VERSION', '1.0.0');

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('UPLOAD_DIR', BASE_PATH . 'uploads/');

// Pagination
define('RECORDS_PER_PAGE', 20);

// Date Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F d, Y');
define('DISPLAY_DATETIME_FORMAT', 'F d, Y h:i A');

// Include Database
require_once __DIR__ . '/database.php';

