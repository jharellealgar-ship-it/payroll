<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

Auth::requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Get statistics based on role
$stats = [];
$isEmployee = Auth::hasRole('employee') && !Auth::hasRole('hr') && !Auth::hasRole('admin');

if ($isEmployee) {
    // Employee sees only their own stats
    $empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = :user_id");
    $empStmt->bindValue(':user_id', $_SESSION['user_id']);
    $empStmt->execute();
    $employeeData = $empStmt->fetch();
    $employeeId = $employeeData['id'] ?? 0;
    
    // Employee's attendance this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE employee_id = :emp_id AND MONTH(attendance_date) = MONTH(CURRENT_DATE()) AND YEAR(attendance_date) = YEAR(CURRENT_DATE())");
    $stmt->bindValue(':emp_id', $employeeId);
    $stmt->execute();
    $stats['attendance_this_month'] = $stmt->fetch()['count'];
    
    // Employee's pending leave requests
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = :emp_id AND status = 'pending'");
    $stmt->bindValue(':emp_id', $employeeId);
    $stmt->execute();
    $stats['pending_leaves'] = $stmt->fetch()['count'];
    
    // Employee's approved leaves this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = :emp_id AND status = 'approved' AND MONTH(start_date) = MONTH(CURRENT_DATE()) AND YEAR(start_date) = YEAR(CURRENT_DATE())");
    $stmt->bindValue(':emp_id', $employeeId);
    $stmt->execute();
    $stats['approved_leaves'] = $stmt->fetch()['count'];
    
    // Employee's total attendance records
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE employee_id = :emp_id");
    $stmt->bindValue(':emp_id', $employeeId);
    $stmt->execute();
    $stats['total_attendance'] = $stmt->fetch()['count'];
} else {
    // Admin/HR sees all stats
    // Total Employees
    $stmt = $conn->query("SELECT COUNT(*) as count FROM employees WHERE employment_status = 'active'");
    $stats['total_employees'] = $stmt->fetch()['count'];

    // Total Payroll Periods
    $stmt = $conn->query("SELECT COUNT(*) as count FROM payroll_periods");
    $stats['total_periods'] = $stmt->fetch()['count'];

    // Pending Leave Requests
    $stmt = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'");
    $stats['pending_leaves'] = $stmt->fetch()['count'];

    // This Month's Payroll
    $stmt = $conn->query("SELECT COUNT(*) as count FROM payroll_periods WHERE MONTH(pay_date) = MONTH(CURRENT_DATE()) AND YEAR(pay_date) = YEAR(CURRENT_DATE())");
    $stats['this_month_payroll'] = $stmt->fetch()['count'];
}

// Recent Activities
$query = "SELECT al.*, u.username, u.first_name, u.last_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC 
          LIMIT 10";
$recentActivities = $conn->query($query)->fetchAll();

// Recent Employees (only for Admin/HR)
$recentEmployees = [];
if (!$isEmployee) {
    $query = "SELECT e.*, u.username 
              FROM employees e 
              LEFT JOIN users u ON e.user_id = u.id 
              ORDER BY e.created_at DESC 
              LIMIT 5";
    $recentEmployees = $conn->query($query)->fetchAll();
}

// Employee's recent attendance (for employees only)
$employeeRecentAttendance = [];
if ($isEmployee && isset($employeeId)) {
    $query = "SELECT a.*, e.employee_id, e.first_name, e.last_name 
              FROM attendance a 
              INNER JOIN employees e ON a.employee_id = e.id 
              WHERE a.employee_id = :emp_id 
              ORDER BY a.attendance_date DESC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':emp_id', $employeeId);
    $stmt->execute();
    $employeeRecentAttendance = $stmt->fetchAll();
}
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php if ($isEmployee): ?>
        <!-- Employee Dashboard Stats -->
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-label">Attendance This Month</div>
                <div class="stat-value"><?php echo $stats['attendance_this_month']; ?></div>
                <i class="bi bi-calendar-check fs-1 opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-label">Pending Leave Requests</div>
                <div class="stat-value"><?php echo $stats['pending_leaves']; ?></div>
                <i class="bi bi-clock-history fs-1 opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-label">Approved Leaves (This Month)</div>
                <div class="stat-value"><?php echo $stats['approved_leaves']; ?></div>
                <i class="bi bi-check-circle fs-1 opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Attendance Records</div>
                <div class="stat-value"><?php echo $stats['total_attendance']; ?></div>
                <i class="bi bi-clock fs-1 opacity-50"></i>
            </div>
        </div>
        <?php else: ?>
        <!-- Admin/HR Dashboard Stats -->
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-label">Active Employees</div>
                <div class="stat-value"><?php echo $stats['total_employees']; ?></div>
                <i class="bi bi-people fs-1 opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-label">Payroll Periods</div>
                <div class="stat-value"><?php echo $stats['total_periods']; ?></div>
                <i class="bi bi-calendar-check fs-1 opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-label">Pending Leaves</div>
                <div class="stat-value"><?php echo $stats['pending_leaves']; ?></div>
                <i class="bi bi-clock-history fs-1 opacity-50"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">This Month</div>
                <div class="stat-value"><?php echo $stats['this_month_payroll']; ?></div>
                <i class="bi bi-cash-coin fs-1 opacity-50"></i>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php if ($isEmployee): ?>
        <!-- Employee's Recent Attendance -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> My Recent Attendance</h5>
                    <a href="<?php echo BASE_URL; ?>attendance/index.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($employeeRecentAttendance) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employeeRecentAttendance as $att): ?>
                                <tr>
                                    <td><?php echo formatDate($att['attendance_date']); ?></td>
                                    <td><?php echo $att['time_in'] ? date('h:i A', strtotime($att['time_in'])) : '-'; ?></td>
                                    <td><?php echo $att['time_out'] ? date('h:i A', strtotime($att['time_out'])) : '-'; ?></td>
                                    <td><?php echo number_format($att['total_hours'], 2); ?> hrs</td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadge($att['status']); ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $att['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">No attendance records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Recent Employees (Admin/HR) -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Recent Employees</h5>
                    <a href="<?php echo BASE_URL; ?>employees/index.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentEmployees) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEmployees as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadge($emp['employment_status']); ?>">
                                            <?php echo ucfirst($emp['employment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">No employees found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activities -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-activity"></i> Recent Activities</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentActivities) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                    <?php if ($activity['username']): ?>
                                    <br><small class="text-muted">by <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo formatDateTime($activity['created_at']); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">No recent activities.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

