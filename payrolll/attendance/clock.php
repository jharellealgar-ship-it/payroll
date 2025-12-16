<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$database = new Database();
$conn = $database->getConnection();

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employeeCode = sanitize($_POST['employee_id'] ?? '');
    $action = $_POST['action'] ?? '';

    if (empty($employeeCode)) {
        $response['message'] = 'Employee ID is required.';
        echo json_encode($response);
        exit;
    }

    // 🔹 Find employee by employee_id code
    $stmt = $conn->prepare("
        SELECT id 
        FROM employees 
        WHERE employee_id = :employee_id 
        AND employment_status = 'active'
        LIMIT 1
    ");
    $stmt->execute(['employee_id' => $employeeCode]);
    $employee = $stmt->fetch();

    if (!$employee) {
        $response['message'] = 'Invalid Employee ID.';
        echo json_encode($response);
        exit;
    }

    $employeeId = $employee['id'];
    $today = date('Y-m-d');
    $now = date('H:i:s');

    // 🔹 Check today's attendance
    $check = $conn->prepare("
        SELECT id, time_in, time_out 
        FROM attendance 
        WHERE employee_id = :employee_id 
        AND attendance_date = :today
        LIMIT 1
    ");
    $check->execute([
        'employee_id' => $employeeId,
        'today' => $today
    ]);

    $attendance = $check->fetch();

    // =====================
    // CLOCK IN
    // =====================
    if ($action === 'in') {

        if ($attendance && !empty($attendance['time_in'])) {
            $response['message'] = 'You have already clocked IN today.';
            echo json_encode($response);
            exit;
        }

        if ($attendance) {
            // Update existing row
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET time_in = :time_in, status = 'present'
                WHERE id = :id
            ");
            $stmt->execute([
                'time_in' => $now,
                'id' => $attendance['id']
            ]);
        } else {
            // Insert new row
            $stmt = $conn->prepare("
                INSERT INTO attendance 
                (employee_id, attendance_date, time_in, status)
                VALUES (:employee_id, :attendance_date, :time_in, 'present')
            ");
            $stmt->execute([
                'employee_id' => $employeeId,
                'attendance_date' => $today,
                'time_in' => $now
            ]);
        }

        $response['success'] = true;
        $response['message'] = 'Clock-in successful.';
    }

    // =====================
    // CLOCK OUT
    // =====================
    if ($action === 'out') {

        if (!$attendance || empty($attendance['time_in'])) {
            $response['message'] = 'You must clock IN first.';
            echo json_encode($response);
            exit;
        }

        if (!empty($attendance['time_out'])) {
            $response['message'] = 'You have already clocked OUT today.';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE attendance 
            SET time_out = :time_out
            WHERE id = :id
        ");
        $stmt->execute([
            'time_out' => $now,
            'id' => $attendance['id']
        ]);

        $response['success'] = true;
        $response['message'] = 'Clock-out successful.';
    }
}

echo json_encode($response);
