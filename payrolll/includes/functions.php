<?php
/**
 * General Helper Functions
 * Professional Payroll Management System
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2, '.', ',');
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * Calculate hours between two times
 */
function calculateHours($timeIn, $timeOut, $breakMinutes = 0) {
    if (empty($timeIn) || empty($timeOut)) {
        return 0;
    }
    
    $start = strtotime($timeIn);
    $end = strtotime($timeOut);
    $totalMinutes = ($end - $start) / 60 - $breakMinutes;
    
    return max(0, round($totalMinutes / 60, 2));
}

/**
 * Calculate late minutes
 */
function calculateLateMinutes($scheduledTime, $actualTime) {
    if (empty($scheduledTime) || empty($actualTime)) {
        return 0;
    }
    
    $scheduled = strtotime($scheduledTime);
    $actual = strtotime($actualTime);
    
    if ($actual > $scheduled) {
        return round(($actual - $scheduled) / 60);
    }
    
    return 0;
}

/**
 * Generate random password
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get role badge color
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => 'danger',
        'hr' => 'primary',
        'accountant' => 'info',
        'employee' => 'secondary'
    ];
    return $badges[$role] ?? 'secondary';
}

/**
 * Get status badge color
 */
function getStatusBadge($status) {
    $badges = [
        'active' => 'success',
        'on-leave' => 'warning',
        'suspended' => 'danger',
        'terminated' => 'dark',
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'draft' => 'secondary',
        'processing' => 'info',
        'completed' => 'success',
        'locked' => 'dark',
        'paid' => 'success',
        'cancelled' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

/**
 * Pagination helper
 */
function getPagination($currentPage, $totalRecords, $recordsPerPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    return [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords,
        'records_per_page' => $recordsPerPage,
        'offset' => ($currentPage - 1) * $recordsPerPage
    ];
}

/**
 * Log activity to audit log
 */
function logActivity($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    require_once __DIR__ . '/../config/config.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
              VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':table_name', $tableName);
    $stmt->bindParam(':record_id', $recordId);
    $stmt->bindValue(':old_values', $oldValues ? json_encode($oldValues) : null);
    $stmt->bindValue(':new_values', $newValues ? json_encode($newValues) : null);
    $stmt->bindParam(':ip_address', $ipAddress);
    $stmt->bindParam(':user_agent', $userAgent);
    
    $stmt->execute();
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Only send header if headers haven't been sent
    if (!headers_sent()) {
        header('Location: ' . BASE_URL . $url);
        exit();
    } else {
        // If headers already sent, use JavaScript redirect
        echo '<script>window.location.href = "' . BASE_URL . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . BASE_URL . $url . '"></noscript>';
        exit();
    }
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

