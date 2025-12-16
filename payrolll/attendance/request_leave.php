<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Get employee ID from user
$userStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = :user_id");
$userStmt->bindValue(':user_id', $_SESSION['user_id']);
$userStmt->execute();
$employee = $userStmt->fetch();

if (!$employee) {
    redirect('index.php', 'Employee record not found.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leaveType = sanitize($_POST['leave_type'] ?? '');
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    
    if (empty($leaveType) || empty($startDate) || empty($endDate) || empty($reason)) {
        redirect('attendance/leave_requests.php', 'Please fill in all required fields.', 'error');
    }
    
    // Calculate days
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $days = $start->diff($end)->days + 1;
    
    $query = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days_requested, reason) 
              VALUES (:employee_id, :leave_type, :start_date, :end_date, :days_requested, :reason)";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':employee_id', $employee['id']);
    $stmt->bindParam(':leave_type', $leaveType);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->bindValue(':days_requested', $days);
    $stmt->bindParam(':reason', $reason);
    
    if ($stmt->execute()) {
        logActivity('Leave Request Submitted', 'leave_requests', $conn->lastInsertId());
        redirect('attendance/leave_requests.php', 'Leave request submitted successfully!', 'success');
    } else {
        redirect('attendance/leave_requests.php', 'Failed to submit leave request.', 'error');
    }
}

redirect('attendance/leave_requests.php');

