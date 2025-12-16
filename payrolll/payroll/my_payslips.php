<?php
$pageTitle = 'My Payslips';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();

// Only employees can access this page
if (!Auth::hasRole('employee') || Auth::hasRole('hr') || Auth::hasRole('admin')) {
    redirect('index.php', 'Access denied.', 'error');
}

$database = new Database();
$conn = $database->getConnection();

// Get employee ID
$empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = :user_id");
$empStmt->bindValue(':user_id', $_SESSION['user_id']);
$empStmt->execute();
$employeeData = $empStmt->fetch();

if (!$employeeData) {
    redirect('index.php', 'Employee record not found.', 'error');
}

$employeeId = $employeeData['id'];

// Get payroll records for this employee
$query = "SELECT pr.*, pp.period_name, pp.start_date, pp.end_date, pp.pay_date, pp.status as period_status
          FROM payroll_records pr
          INNER JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
          WHERE pr.employee_id = :emp_id
          ORDER BY pp.pay_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindValue(':emp_id', $employeeId);
$stmt->execute();
$payslips = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">My Payslips</h1>
            <p class="text-muted">View your payroll records and payslips</p>
        </div>
    </div>

    <?php if (count($payslips) > 0): ?>
    <div class="row">
        <?php foreach ($payslips as $payslip): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($payslip['period_name']); ?></h5>
                        <span class="badge bg-<?php echo getStatusBadge($payslip['period_status']); ?>">
                            <?php echo ucfirst($payslip['period_status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6"><strong>Pay Period:</strong></div>
                        <div class="col-6">
                            <?php echo formatDate($payslip['start_date']); ?> - <?php echo formatDate($payslip['end_date']); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><strong>Pay Date:</strong></div>
                        <div class="col-6"><?php echo formatDate($payslip['pay_date']); ?></div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-6">Basic Salary:</div>
                        <div class="col-6 text-end"><?php echo formatCurrency($payslip['basic_salary']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Overtime Pay:</div>
                        <div class="col-6 text-end"><?php echo formatCurrency($payslip['overtime_pay']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Gross Salary:</strong></div>
                        <div class="col-6 text-end"><strong><?php echo formatCurrency($payslip['gross_salary']); ?></strong></div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-6">Tax:</div>
                        <div class="col-6 text-end text-danger">-<?php echo formatCurrency($payslip['tax_deduction']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">SSS:</div>
                        <div class="col-6 text-end text-danger">-<?php echo formatCurrency($payslip['sss_deduction']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">PhilHealth:</div>
                        <div class="col-6 text-end text-danger">-<?php echo formatCurrency($payslip['philhealth_deduction']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Pag-IBIG:</div>
                        <div class="col-6 text-end text-danger">-<?php echo formatCurrency($payslip['pagibig_deduction']); ?></div>
                    </div>
                    <?php if ($payslip['late_deduction'] > 0): ?>
                    <div class="row mb-2">
                        <div class="col-6">Late Deduction:</div>
                        <div class="col-6 text-end text-danger">-<?php echo formatCurrency($payslip['late_deduction']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($payslip['total_incentives'] > 0): ?>
                    <div class="row mb-2">
                        <div class="col-6">Incentives:</div>
                        <div class="col-6 text-end text-success">+<?php echo formatCurrency($payslip['total_incentives']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Total Deductions:</strong></div>
                        <div class="col-6 text-end"><strong class="text-danger">-<?php echo formatCurrency($payslip['total_deductions']); ?></strong></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6"><h5>Net Pay:</h5></div>
                        <div class="col-6 text-end"><h5 class="text-success"><?php echo formatCurrency($payslip['net_pay']); ?></h5></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-3">No payslips found.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

