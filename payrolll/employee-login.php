<?php
$pageTitle = 'Employee Attendance';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$database = new Database();
$conn = $database->getConnection();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employeeCode = sanitize($_POST['employee_id'] ?? '');
    $action = $_POST['action'] ?? '';

    if (empty($employeeCode)) {
        $message = 'Please enter your Employee ID.';
        $messageType = 'danger';
    } elseif (!in_array($action, ['in', 'out'])) {
        $message = 'Invalid action.';
        $messageType = 'danger';
    } else {

        // 🔹 Find employee by employee_id code
        $stmt = $conn->prepare("
            SELECT id 
            FROM employees 
            WHERE employee_id = :employee_id 
              AND employment_status = 'active'
            LIMIT 1
        ");
        $stmt->execute(['employee_id' => $employeeCode]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            $message = 'Invalid or inactive Employee ID.';
            $messageType = 'danger';
        } else {

            $employeeId = $employee['id'];
            $today = date('Y-m-d');
            $now = date('H:i:s');

            // 🔹 Check if attendance exists for today
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
            $attendance = $check->fetch(PDO::FETCH_ASSOC);

            // =====================
            // CLOCK IN
            // =====================
            if ($action === 'in') {

                if ($attendance && !empty($attendance['time_in'])) {
                    $message = 'You have already clocked IN today.';
                    $messageType = 'warning';
                } else {

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
                        // Insert new attendance record
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

                    $message = 'Clock-in successful. Have a great day!';
                    $messageType = 'success';
                }
            }

            // =====================
            // CLOCK OUT
            // =====================
            if ($action === 'out') {

                if (!$attendance || empty($attendance['time_in'])) {
                    $message = 'You must clock IN first.';
                    $messageType = 'danger';
                } elseif (!empty($attendance['time_out'])) {
                    $message = 'You have already clocked OUT today.';
                    $messageType = 'warning';
                } else {

                    $stmt = $conn->prepare("
                        UPDATE attendance 
                        SET time_out = :time_out
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'time_out' => $now,
                        'id' => $attendance['id']
                    ]);

                    $message = 'Clock-out successful. Goodbye!';
                    $messageType = 'success';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <div class="login-card attendance-card">

        <div class="login-logo">
            <i class="bi bi-person-badge"></i>
            <h2>Employee Attendance</h2>
            <p class="text-muted"><?php echo APP_NAME; ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> text-center">
                <i class="bi bi-info-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label">Employee ID</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">
                        <i class="bi bi-credit-card"></i>
                    </span>
                    <input type="text"
                           class="form-control"
                           name="employee_id"
                           placeholder="Enter Employee ID"
                           required
                           autofocus>
                </div>
            </div>

            <div class="d-grid gap-3">
                <button type="submit" name="action" value="in" class="btn btn-success btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i> IN
                </button>

                <button type="submit" name="action" value="out" class="btn btn-danger btn-lg">
                    <i class="bi bi-box-arrow-left me-2"></i> OUT
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">
                Please clock IN when arriving and OUT when leaving.
            </small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
