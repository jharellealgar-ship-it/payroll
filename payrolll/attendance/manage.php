<?php
$pageTitle = 'Manage Attendance';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('hr');

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $attendanceDate = sanitize($_POST['attendance_date'] ?? '');
    $timeIn = sanitize($_POST['time_in'] ?? '');
    $timeOut = sanitize($_POST['time_out'] ?? '');
    $breakDuration = intval($_POST['break_duration'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'present');
    
    if (empty($employeeId) || empty($attendanceDate)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Calculate total hours
        $totalHours = 0;
        $overtimeHours = 0;
        $lateMinutes = 0;
        
        if (!empty($timeIn) && !empty($timeOut)) {
            $totalHours = calculateHours($timeIn, $timeOut, $breakDuration);
            
            // Calculate overtime (assuming 8 hours regular)
            if ($totalHours > 8) {
                $overtimeHours = $totalHours - 8;
            }
            
            // Calculate late (assuming 9:00 AM scheduled time)
            if (!empty($timeIn)) {
                $lateMinutes = calculateLateMinutes('09:00:00', $timeIn);
            }
        }
        
        // Check if record already exists
        $checkStmt = $conn->prepare("SELECT id FROM attendance WHERE employee_id = :employee_id AND attendance_date = :attendance_date");
        $checkStmt->bindParam(':employee_id', $employeeId);
        $checkStmt->bindParam(':attendance_date', $attendanceDate);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Update existing record
            $query = "UPDATE attendance SET time_in = :time_in, time_out = :time_out, break_duration = :break_duration, 
                      total_hours = :total_hours, overtime_hours = :overtime_hours, late_minutes = :late_minutes, 
                      status = :status, remarks = :remarks 
                      WHERE employee_id = :employee_id AND attendance_date = :attendance_date";
        } else {
            // Insert new record
            $query = "INSERT INTO attendance (employee_id, attendance_date, time_in, time_out, break_duration, 
                      total_hours, overtime_hours, late_minutes, status, remarks) 
                      VALUES (:employee_id, :attendance_date, :time_in, :time_out, :break_duration, 
                      :total_hours, :overtime_hours, :late_minutes, :status, :remarks)";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':employee_id', $employeeId);
        $stmt->bindValue(':attendance_date', $attendanceDate);
        $stmt->bindValue(':time_in', !empty($timeIn) ? $timeIn : null);
        $stmt->bindValue(':time_out', !empty($timeOut) ? $timeOut : null);
        $stmt->bindValue(':break_duration', $breakDuration);
        $stmt->bindValue(':total_hours', $totalHours);
        $stmt->bindValue(':overtime_hours', $overtimeHours);
        $stmt->bindValue(':late_minutes', $lateMinutes);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':remarks', sanitize($_POST['remarks'] ?? ''));
        
        if ($stmt->execute()) {
            logActivity('Attendance Recorded', 'attendance', $conn->lastInsertId());
            redirect('attendance/index.php', 'Attendance recorded successfully!', 'success');
        } else {
            $error = 'Failed to record attendance. Please try again.';
        }
    }
}

// Get employees
$employees = $conn->query("SELECT id, employee_id, first_name, last_name FROM employees WHERE employment_status = 'active' ORDER BY last_name, first_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Record Attendance</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>attendance/index.php">Attendance</a></li>
                    <li class="breadcrumb-item active">Record Attendance</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Attendance Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee <span class="text-danger">*</span></label>
                                <select class="form-select" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Time In</label>
                                <input type="time" class="form-control time-picker calculate-hours" name="time_in">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Time Out</label>
                                <input type="time" class="form-control time-picker calculate-hours" name="time_out">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Break Duration (minutes)</label>
                                <input type="number" class="form-control calculate-hours" name="break_duration" value="0" min="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="on-leave">On Leave</option>
                                    <option value="half-day">Half Day</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Total Hours (auto-calculated)</label>
                                <input type="text" class="form-control total-hours" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-save"></i> Save Attendance
                        </button>
                        <a href="<?php echo BASE_URL; ?>attendance/index.php" class="btn btn-secondary w-100">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

