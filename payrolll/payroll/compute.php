<?php
$pageTitle = 'Compute Payroll';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('accountant');

// Check if user is admin (for special bonus functionality)
$isAdmin = Auth::hasRole('admin');

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

// Get system settings
$settings = [];
$settingsQuery = $conn->query("SELECT setting_key, setting_value FROM system_settings");
foreach ($settingsQuery->fetchAll() as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$regularHoursPerDay = floatval($settings['regular_hours_per_day'] ?? 8);
$overtimeRate = floatval($settings['overtime_rate_multiplier'] ?? 1.25);
$taxRate = floatval($settings['tax_rate'] ?? 0.20);
$sssRate = floatval($settings['sss_rate'] ?? 0.11);
$philhealthRate = floatval($settings['philhealth_rate'] ?? 0.03);
$pagibigRate = floatval($settings['pagibig_rate'] ?? 0.02);
$latePenaltyPerMinute = floatval($settings['late_penalty_per_minute'] ?? 10);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $periodId = intval($_POST['period_id'] ?? 0);
    $employeeIds = $_POST['employee_ids'] ?? [];
    
    // Get special bonuses (admin only)
    $specialBonuses = [];
    if ($isAdmin && isset($_POST['special_bonuses'])) {
        foreach ($_POST['special_bonuses'] as $empId => $bonusAmount) {
            $specialBonuses[intval($empId)] = floatval($bonusAmount);
        }
    }
    
    if (empty($periodId) || empty($employeeIds)) {
        $error = 'Please select a payroll period and at least one employee.';
    } else {
        // Get period details
        $periodStmt = $conn->prepare("SELECT * FROM payroll_periods WHERE id = :id");
        $periodStmt->bindParam(':id', $periodId);
        $periodStmt->execute();
        $period = $periodStmt->fetch();
        
        if (!$period) {
            $error = 'Payroll period not found.';
        } else {
            $conn->beginTransaction();
            $successCount = 0;
            
            try {
                foreach ($employeeIds as $empId) {
                    $empId = intval($empId);
                    
                    // Get employee
                    $empStmt = $conn->prepare("SELECT * FROM employees WHERE id = :id");
                    $empStmt->bindParam(':id', $empId);
                    $empStmt->execute();
                    $employee = $empStmt->fetch();
                    
                    if (!$employee) continue;
                    
                    // ============================================================
                    // ATTENDANCE-BASED PAYROLL COMPUTATION
                    // ============================================================
                    // This section computes payroll based on actual attendance records
                    // within the selected payroll period (e.g., 5-20 or 21-4).
                    // It calculates:
                    // - Working days in period (excluding weekends)
                    // - Days worked vs absences
                    // - Regular hours and overtime from attendance records
                    // - Late minutes and deductions
                    // - Prorated salary based on actual attendance
                    // ============================================================
                    
                    // Get attendance for the period - ensure proper connection
                    $attStmt = $conn->prepare("SELECT * FROM attendance 
                                               WHERE employee_id = :emp_id 
                                               AND attendance_date >= :start_date 
                                               AND attendance_date <= :end_date
                                               ORDER BY attendance_date ASC");
                    $attStmt->bindParam(':emp_id', $empId, PDO::PARAM_INT);
                    $attStmt->bindParam(':start_date', $period['start_date'], PDO::PARAM_STR);
                    $attStmt->bindParam(':end_date', $period['end_date'], PDO::PARAM_STR);
                    $attStmt->execute();
                    $attendanceRecords = $attStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Create a map of attendance by date for quick lookup
                    $attendanceByDate = [];
                    foreach ($attendanceRecords as $att) {
                        $attendanceByDate[$att['attendance_date']] = $att;
                    }
                    
                    // Calculate total working days in the period (excluding weekends)
                    $startDate = new DateTime($period['start_date']);
                    $endDate = new DateTime($period['end_date']);
                    $endDate->modify('+1 day'); // Include end date
                    
                    $totalWorkingDaysInPeriod = 0;
                    $currentDate = clone $startDate;
                    $attendanceDates = [];
                    
                    while ($currentDate < $endDate) {
                        $dayOfWeek = $currentDate->format('w'); // 0 = Sunday, 6 = Saturday
                        // Count only weekdays (Monday to Friday)
                        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                            $totalWorkingDaysInPeriod++;
                            $attendanceDates[] = $currentDate->format('Y-m-d');
                        }
                        $currentDate->modify('+1 day');
                    }
                    
                    // Initialize variables
                    $totalRegularHours = 0;
                    $totalOvertimeHours = 0;
                    $totalLateMinutes = 0;
                    $totalAbsences = 0;
                    $hasWorkedDays = false;
                    $workedDaysCount = 0; // Count actual days worked for prorating salary
                    $onLeaveDays = 0; // Count approved leave days
                    
                    // Process each working day in the period
                    foreach ($attendanceDates as $date) {
                        if (isset($attendanceByDate[$date])) {
                            $att = $attendanceByDate[$date];
                            $status = strtolower(trim($att['status'] ?? ''));
                            
                            // Check for present or half-day status
                            if ($status == 'present' || $status == 'half-day') {
                                $hasWorkedDays = true;
                                
                                // Calculate hours based on time_in and time_out (when they logged in and logged out)
                                $timeIn = $att['time_in'] ?? null;
                                $timeOut = $att['time_out'] ?? null;
                                $breakDuration = intval($att['break_duration'] ?? 0);
                                
                                if (!empty($timeIn) && !empty($timeOut)) {
                                    // Calculate total hours worked from time_in to time_out
                                    $dayTotalHours = calculateHours($timeIn, $timeOut, $breakDuration);
                                    
                                    // Handle half-day status
                                    if ($status == 'half-day') {
                                        // For half-day, use half of regular hours or actual hours (whichever is less)
                                        $dayTotalHours = min($dayTotalHours, $regularHoursPerDay / 2);
                                    }
                                    
                                    // Separate regular hours and overtime hours
                                    if ($dayTotalHours > $regularHoursPerDay) {
                                        // Has overtime
                                        $regularHoursForDay = $regularHoursPerDay;
                                        $overtimeHoursForDay = $dayTotalHours - $regularHoursPerDay;
                                    } else {
                                        // No overtime
                                        $regularHoursForDay = $dayTotalHours;
                                        $overtimeHoursForDay = 0;
                                    }
                                    
                                    $totalRegularHours += $regularHoursForDay;
                                    $totalOvertimeHours += $overtimeHoursForDay;
                                    
                                    // Count worked days for salary calculation
                                    if ($status == 'present') {
                                        $workedDaysCount += 1.0;
                                    } elseif ($status == 'half-day') {
                                        $workedDaysCount += 0.5;
                                    }
                                } else {
                                    // If time_out is not recorded yet (clocked in but not out), don't count this day
                                    // Payroll computation only counts complete attendance records (both time_in and time_out)
                                    // This ensures payroll is based on actual logged hours (when they logged out)
                                    // The day will be treated as incomplete and not included in payroll calculation
                                }
                                
                                // Calculate late minutes based on time_in (if scheduled time is available)
                                if (!empty($timeIn)) {
                                    // Default scheduled time is 9:00 AM, but you can get this from settings
                                    $scheduledTime = '09:00:00'; // You can make this configurable
                                    $lateMins = calculateLateMinutes($scheduledTime, $timeIn);
                                    $totalLateMinutes += $lateMins;
                                } else {
                                    // Use stored late minutes if time_in is not available
                                    $totalLateMinutes += intval($att['late_minutes'] ?? 0);
                                }
                            } elseif ($status == 'on-leave') {
                                // Count approved leave days (not deducted from salary)
                                $onLeaveDays++;
                            } elseif ($status == 'absent') {
                                // Count absences (deducted from salary)
                                $totalAbsences++;
                            }
                        } else {
                            // No attendance record for this working day = absence
                            $totalAbsences++;
                        }
                    }
                    
                    // Initialize all payroll variables
                    $monthlySalary = floatval($employee['base_salary'] ?? 0);
                    $basicSalary = 0;
                    $overtimePay = 0;
                    $grossSalary = 0;
                    $lateDeduction = 0;
                    $absenceDeduction = 0;
                    $sssDeduction = 0;
                    $philhealthDeduction = 0;
                    $pagibigDeduction = 0;
                    $taxDeduction = 0;
                    $totalDeductions = 0;
                    $totalIncentives = 0;
                    $netPay = 0;
                    
                    // Calculate payroll - properly connected to attendance data within payroll period
                    if ($monthlySalary > 0) {
                        // Basic Salary: Prorated based on actual days worked within the period
                        // Calculate based on attendance - days worked / total working days in period
                        if ($totalWorkingDaysInPeriod > 0 && $workedDaysCount > 0) {
                            // Prorate salary: (days worked / total working days in period) × (monthly salary × period ratio)
                            // Period ratio: For bi-monthly periods, each period is approximately half a month
                            // Calculate period ratio based on actual days in period vs days in month
                            $periodStart = new DateTime($period['start_date']);
                            $periodEnd = new DateTime($period['end_date']);
                            $periodEnd->modify('+1 day'); // Include end date
                            $daysInPeriod = $periodStart->diff($periodEnd)->days;
                            
                            // Estimate days in the month (use period start month)
                            $monthStart = new DateTime($periodStart->format('Y-m-01'));
                            $monthEnd = clone $monthStart;
                            $monthEnd->modify('+1 month');
                            $daysInMonth = $monthStart->diff($monthEnd)->days;
                            
                            // Period ratio: how much of the month this period represents
                            $periodRatio = $daysInPeriod / $daysInMonth;
                            
                            // Expected salary for this period
                            $periodExpectedSalary = $monthlySalary * $periodRatio;
                            
                            // Actual salary based on days worked
                            $basicSalary = ($workedDaysCount / $totalWorkingDaysInPeriod) * $periodExpectedSalary;
                        } else {
                            $basicSalary = 0;
                        }
                        
                        // Calculate hourly rate for overtime and deductions
                        $hourlyRate = floatval($employee['hourly_rate'] ?? 0);
                        if ($hourlyRate <= 0) {
                            // Calculate hourly rate based on monthly salary and standard working days
                            $standardWorkingDaysPerMonth = 22; // Standard working days per month
                            $hourlyRate = $monthlySalary / ($standardWorkingDaysPerMonth * $regularHoursPerDay);
                        }
                        
                        // Overtime Pay: Based on overtime hours from attendance
                        $overtimePay = $totalOvertimeHours * $hourlyRate * $overtimeRate;
                        
                        // Gross Salary: Basic + Overtime (from attendance)
                        $grossSalary = $basicSalary + $overtimePay;
                        
                        // Deductions based on attendance data within the payroll period
                        // Late deduction from attendance late_minutes
                        $lateDeduction = $totalLateMinutes * $latePenaltyPerMinute;
                        
                        // Absence deduction: Calculate daily rate based on period expected salary
                        if ($totalWorkingDaysInPeriod > 0) {
                            $periodStart = new DateTime($period['start_date']);
                            $periodEnd = new DateTime($period['end_date']);
                            $periodEnd->modify('+1 day');
                            $daysInPeriod = $periodStart->diff($periodEnd)->days;
                            
                            $monthStart = new DateTime($periodStart->format('Y-m-01'));
                            $monthEnd = clone $monthStart;
                            $monthEnd->modify('+1 month');
                            $daysInMonth = $monthStart->diff($monthEnd)->days;
                            $periodRatio = $daysInPeriod / $daysInMonth;
                            $periodExpectedSalary = $monthlySalary * $periodRatio;
                            
                            // Daily rate for this period
                            $dailyRate = $periodExpectedSalary / $totalWorkingDaysInPeriod;
                            $absenceDeduction = $totalAbsences * $dailyRate;
                        } else {
                            $dailyRate = $hourlyRate * $regularHoursPerDay;
                            $absenceDeduction = $totalAbsences * $dailyRate;
                        }
                        
                        // Government deductions based on gross salary
                        $sssDeduction = $grossSalary * $sssRate;
                        $philhealthDeduction = $grossSalary * $philhealthRate;
                        $pagibigDeduction = $grossSalary * $pagibigRate;
                        $taxDeduction = $grossSalary * $taxRate;
                        
                        // Total deductions
                        $totalDeductions = $lateDeduction + $absenceDeduction + $sssDeduction + 
                                          $philhealthDeduction + $pagibigDeduction + $taxDeduction;
                        
                        // Get Incentives for the period
                        $incStmt = $conn->prepare("SELECT SUM(amount) as total FROM employee_incentives 
                                                   WHERE employee_id = :emp_id AND is_active = 1 
                                                   AND start_date <= :end_date 
                                                   AND (end_date IS NULL OR end_date >= :start_date)");
                        $incStmt->bindParam(':emp_id', $empId, PDO::PARAM_INT);
                        $incStmt->bindParam(':start_date', $period['start_date'], PDO::PARAM_STR);
                        $incStmt->bindParam(':end_date', $period['end_date'], PDO::PARAM_STR);
                        $incStmt->execute();
                        $incentives = $incStmt->fetch(PDO::FETCH_ASSOC);
                        $totalIncentives = floatval($incentives['total'] ?? 0);
                        
                        // Special Bonus (Admin Only)
                        $specialBonus = 0;
                        if ($isAdmin && isset($specialBonuses[$empId])) {
                            $specialBonus = $specialBonuses[$empId];
                        }
                        
                        // Total Incentives: Regular incentives + Special Bonus
                        $totalIncentives = $totalIncentives + $specialBonus;
                        
                        // Net Pay: Gross - Deductions + Incentives (including special bonus)
                        $netPay = $grossSalary - $totalDeductions + $totalIncentives;
                    }
                    
                    // Insert or update payroll record
                    $checkStmt = $conn->prepare("SELECT id FROM payroll_records 
                                                 WHERE payroll_period_id = :period_id AND employee_id = :emp_id");
                    $checkStmt->bindParam(':period_id', $periodId);
                    $checkStmt->bindParam(':emp_id', $empId);
                    $checkStmt->execute();
                    
                    if ($checkStmt->rowCount() > 0) {
                        // Update
                        $payrollQuery = "UPDATE payroll_records SET 
                                        basic_salary = :basic_salary, regular_hours = :regular_hours, 
                                        overtime_hours = :overtime_hours, overtime_pay = :overtime_pay,
                                        gross_salary = :gross_salary, tax_deduction = :tax_deduction,
                                        sss_deduction = :sss_deduction, philhealth_deduction = :philhealth_deduction,
                                        pagibig_deduction = :pagibig_deduction, late_deduction = :late_deduction,
                                        absence_deduction = :absence_deduction, total_deductions = :total_deductions,
                                        bonus = :bonus, allowance = :allowance, incentive = :incentive,
                                        total_incentives = :total_incentives, net_pay = :net_pay
                                        WHERE payroll_period_id = :period_id AND employee_id = :emp_id";
                    } else {
                        // Insert
                        $payrollQuery = "INSERT INTO payroll_records 
                                        (payroll_period_id, employee_id, basic_salary, regular_hours, 
                                        overtime_hours, overtime_pay, gross_salary, tax_deduction,
                                        sss_deduction, philhealth_deduction, pagibig_deduction, 
                                        late_deduction, absence_deduction, total_deductions,
                                        bonus, allowance, incentive, total_incentives, net_pay) 
                                        VALUES 
                                        (:period_id, :emp_id, :basic_salary, :regular_hours, 
                                        :overtime_hours, :overtime_pay, :gross_salary, :tax_deduction,
                                        :sss_deduction, :philhealth_deduction, :pagibig_deduction, 
                                        :late_deduction, :absence_deduction, :total_deductions,
                                        :bonus, :allowance, :incentive, :total_incentives, :net_pay)";
                    }
                    
                    $payrollStmt = $conn->prepare($payrollQuery);
                    $payrollStmt->bindParam(':period_id', $periodId);
                    $payrollStmt->bindParam(':emp_id', $empId);
                    $payrollStmt->bindValue(':basic_salary', $basicSalary);
                    $payrollStmt->bindValue(':regular_hours', $totalRegularHours);
                    $payrollStmt->bindValue(':overtime_hours', $totalOvertimeHours);
                    $payrollStmt->bindValue(':overtime_pay', $overtimePay);
                    $payrollStmt->bindValue(':gross_salary', $grossSalary);
                    $payrollStmt->bindValue(':tax_deduction', $taxDeduction);
                    $payrollStmt->bindValue(':sss_deduction', $sssDeduction);
                    $payrollStmt->bindValue(':philhealth_deduction', $philhealthDeduction);
                    $payrollStmt->bindValue(':pagibig_deduction', $pagibigDeduction);
                    $payrollStmt->bindValue(':late_deduction', $lateDeduction);
                    $payrollStmt->bindValue(':absence_deduction', $absenceDeduction);
                    $payrollStmt->bindValue(':total_deductions', $totalDeductions);
                    // Special Bonus (Admin Only) - stored in bonus field
                    $specialBonusValue = ($isAdmin && isset($specialBonuses[$empId])) ? $specialBonuses[$empId] : 0;
                    $payrollStmt->bindValue(':bonus', $specialBonusValue);
                    $payrollStmt->bindValue(':allowance', 0);
                    // Incentive field stores regular incentives (excluding special bonus)
                    $regularIncentives = $totalIncentives - $specialBonusValue;
                    $payrollStmt->bindValue(':incentive', $regularIncentives);
                    $payrollStmt->bindValue(':total_incentives', $totalIncentives);
                    $payrollStmt->bindValue(':net_pay', $netPay);
                    $payrollStmt->execute();
                    
                    $successCount++;
                }
                
                $conn->commit();
                redirect('payroll/periods.php', "Payroll computed successfully for $successCount employee(s)!", 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Error computing payroll: ' . $e->getMessage();
            }
        }
    }
}

// Get payroll periods
$periods = $conn->query("SELECT * FROM payroll_periods ORDER BY start_date DESC")->fetchAll();

// Get active employees
$employees = $conn->query("SELECT id, employee_id, first_name, last_name, base_salary FROM employees WHERE employment_status = 'active' ORDER BY last_name, first_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Compute Payroll</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>payroll/periods.php">Payroll Periods</a></li>
                    <li class="breadcrumb-item active">Compute Payroll</li>
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
                        <h5 class="mb-0">Payroll Computation</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Payroll Period <span class="text-danger">*</span></label>
                            <select class="form-select" name="period_id" required>
                                <option value="">Select Period</option>
                                <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['id']; ?>">
                                    <?php echo htmlspecialchars($period['period_name'] . ' (' . formatDate($period['start_date']) . ' - ' . formatDate($period['end_date']) . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Employees <span class="text-danger">*</span></label>
                            
                            <!-- Search Bar -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="employeeSearch" 
                                           placeholder=" Name, or Salary...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </div>
                                <small class="text-muted">Type to filter employees in real-time</small>
                            </div>
                            
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;" id="employeeListContainer">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll">
                                        <strong>Select All</strong>
                                    </label>
                                </div>
                                <hr>
                                <div id="employeeList">
                                    <?php foreach ($employees as $emp): ?>
                                    <div class="mb-3 pb-2 border-bottom employee-item" 
                                         data-emp-id="<?php echo $emp['id']; ?>"
                                         data-emp-code="<?php echo strtolower(htmlspecialchars($emp['employee_id'])); ?>"
                                         data-emp-name="<?php echo strtolower(htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'])); ?>"
                                         data-emp-salary="<?php echo htmlspecialchars($emp['base_salary']); ?>">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input employee-checkbox" type="checkbox" name="employee_ids[]" value="<?php echo $emp['id']; ?>" id="emp_<?php echo $emp['id']; ?>" data-emp-id="<?php echo $emp['id']; ?>">
                                            <label class="form-check-label" for="emp_<?php echo $emp['id']; ?>">
                                                <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                                <small class="text-muted">(<?php echo formatCurrency($emp['base_salary']); ?>)</small>
                                            </label>
                                        </div>
                                        <?php if ($isAdmin): ?>
                                        <div class="ms-4 mt-2">
                                            <label class="form-label small">Special Bonus (Admin Only)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" 
                                                       class="form-control special-bonus" 
                                                       name="special_bonuses[<?php echo $emp['id']; ?>]" 
                                                       id="bonus_<?php echo $emp['id']; ?>"
                                                       value="0" 
                                                       step="0.01" 
                                                       min="0" 
                                                       placeholder="0.00"
                                                       data-emp-id="<?php echo $emp['id']; ?>">
                                            </div>
                                            <small class="text-muted">Additional bonus for this payroll period</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div id="noResults" class="text-center py-4 text-muted" style="display: none;">
                                    <i class="bi bi-search fs-1"></i>
                                    <p class="mt-2">No employees found matching your search.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Computation Settings</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Regular Hours/Day:</strong> <?php echo $regularHoursPerDay; ?></p>
                        <p><strong>Overtime Rate:</strong> <?php echo ($overtimeRate * 100); ?>%</p>
                        <p><strong>Tax Rate:</strong> <?php echo ($taxRate * 100); ?>%</p>
                        <p><strong>SSS Rate:</strong> <?php echo ($sssRate * 100); ?>%</p>
                        <p><strong>PhilHealth Rate:</strong> <?php echo ($philhealthRate * 100); ?>%</p>
                        <p><strong>Pag-IBIG Rate:</strong> <?php echo ($pagibigRate * 100); ?>%</p>
                        <?php if ($isAdmin): ?>
                        <hr>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-shield-check"></i>
                            <strong>Admin Mode:</strong> You can add special bonuses for employees.
                        </div>
                        <?php endif; ?>
                        <hr>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-calculator"></i> Compute Payroll
                        </button>
                        <a href="<?php echo BASE_URL; ?>payroll/periods.php" class="btn btn-secondary w-100 mt-2">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Employee Search Functionality
const employeeSearch = document.getElementById('employeeSearch');
const clearSearchBtn = document.getElementById('clearSearch');
const employeeItems = document.querySelectorAll('.employee-item');
const noResults = document.getElementById('noResults');

function filterEmployees() {
    const searchTerm = employeeSearch.value.toLowerCase().trim();
    let visibleCount = 0;
    
    employeeItems.forEach(item => {
        const empCode = item.dataset.empCode || '';
        const empName = item.dataset.empName || '';
        const empSalary = item.dataset.empSalary || '';
        
        // Search in employee ID, name, and salary
        const matches = empCode.includes(searchTerm) || 
                       empName.includes(searchTerm) || 
                       empSalary.includes(searchTerm);
        
        if (matches || searchTerm === '') {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show/hide "no results" message
    if (visibleCount === 0 && searchTerm !== '') {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
}

// Search input event
employeeSearch.addEventListener('input', filterEmployees);

// Clear search button
clearSearchBtn.addEventListener('click', function() {
    employeeSearch.value = '';
    filterEmployees();
    employeeSearch.focus();
});

// Select all employees (only visible/filtered ones)
document.getElementById('selectAll').addEventListener('change', function() {
    // Only select visible checkboxes (those not hidden by filter)
    employeeItems.forEach(item => {
        if (item.style.display !== 'none') {
            const checkbox = item.querySelector('.employee-checkbox');
            if (checkbox) {
                checkbox.checked = this.checked;
                // Enable/disable special bonus fields based on checkbox state
                const empId = checkbox.dataset.empId;
                const bonusInput = document.getElementById('bonus_' + empId);
                if (bonusInput) {
                    bonusInput.disabled = !this.checked;
                }
            }
        }
    });
});

// Enable/disable special bonus field when employee checkbox is toggled
document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const empId = this.dataset.empId;
        const bonusInput = document.getElementById('bonus_' + empId);
        if (bonusInput) {
            bonusInput.disabled = !this.checked;
            if (!this.checked) {
                bonusInput.value = '0';
            }
        }
    });
    
    // Initialize disabled state
    const empId = checkbox.dataset.empId;
    const bonusInput = document.getElementById('bonus_' + empId);
    if (bonusInput) {
        bonusInput.disabled = !checkbox.checked;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

