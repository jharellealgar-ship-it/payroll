<?php
$pageTitle = 'Payroll Periods';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('accountant');

$database = new Database();
$conn = $database->getConnection();

// Handle auto-generate periods for a month
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auto_generate'])) {
    $month = intval($_POST['month'] ?? date('m'));
    $year = intval($_POST['year'] ?? date('Y'));
    
    if ($month >= 1 && $month <= 12 && $year >= 2020) {
        try {
            $conn->beginTransaction();
            
            // Period 1: 5th to 20th
            $period1Start = sprintf('%04d-%02d-05', $year, $month);
            $period1End = sprintf('%04d-%02d-20', $year, $month);
            $period1PayDate = sprintf('%04d-%02d-21', $year, $month);
            $period1Name = date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' - First Period (5-20)';
            
            // Check if period already exists
            $check1 = $conn->prepare("SELECT id FROM payroll_periods WHERE start_date = :start_date AND end_date = :end_date");
            $check1->execute(['start_date' => $period1Start, 'end_date' => $period1End]);
            
            if ($check1->rowCount() == 0) {
                $stmt1 = $conn->prepare("INSERT INTO payroll_periods (period_name, start_date, end_date, pay_date, created_by) 
                                         VALUES (:period_name, :start_date, :end_date, :pay_date, :created_by)");
                $stmt1->execute([
                    'period_name' => $period1Name,
                    'start_date' => $period1Start,
                    'end_date' => $period1End,
                    'pay_date' => $period1PayDate,
                    'created_by' => $_SESSION['user_id']
                ]);
                logActivity('Payroll Period Created', 'payroll_periods', $conn->lastInsertId());
            }
            
            // Period 2: 21st to 4th of next month
            $nextMonth = $month + 1;
            $nextYear = $year;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            
            $period2Start = sprintf('%04d-%02d-21', $year, $month);
            $period2End = sprintf('%04d-%02d-04', $nextYear, $nextMonth);
            $period2PayDate = sprintf('%04d-%02d-05', $nextYear, $nextMonth);
            $period2Name = date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' - Second Period (21-' . date('M d', mktime(0, 0, 0, $nextMonth, 4, $nextYear)) . ')';
            
            // Check if period already exists
            $check2 = $conn->prepare("SELECT id FROM payroll_periods WHERE start_date = :start_date AND end_date = :end_date");
            $check2->execute(['start_date' => $period2Start, 'end_date' => $period2End]);
            
            if ($check2->rowCount() == 0) {
                $stmt2 = $conn->prepare("INSERT INTO payroll_periods (period_name, start_date, end_date, pay_date, created_by) 
                                         VALUES (:period_name, :start_date, :end_date, :pay_date, :created_by)");
                $stmt2->execute([
                    'period_name' => $period2Name,
                    'start_date' => $period2Start,
                    'end_date' => $period2End,
                    'pay_date' => $period2PayDate,
                    'created_by' => $_SESSION['user_id']
                ]);
                logActivity('Payroll Period Created', 'payroll_periods', $conn->lastInsertId());
            }
            
            $conn->commit();
            redirect('payroll/periods.php', 'Payroll periods generated successfully!', 'success');
        } catch (Exception $e) {
            $conn->rollBack();
            redirect('payroll/periods.php', 'Error generating periods: ' . $e->getMessage(), 'danger');
        }
    }
}

// Handle period creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_period'])) {
    $periodName = sanitize($_POST['period_name'] ?? '');
    $startDate = sanitize($_POST['start_date'] ?? '');
    $endDate = sanitize($_POST['end_date'] ?? '');
    $payDate = sanitize($_POST['pay_date'] ?? '');
    
    if (!empty($periodName) && !empty($startDate) && !empty($endDate) && !empty($payDate)) {
        $query = "INSERT INTO payroll_periods (period_name, start_date, end_date, pay_date, created_by) 
                  VALUES (:period_name, :start_date, :end_date, :pay_date, :created_by)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':period_name', $periodName);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':pay_date', $payDate);
        $stmt->bindValue(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            logActivity('Payroll Period Created', 'payroll_periods', $conn->lastInsertId());
            redirect('payroll/periods.php', 'Payroll period created successfully!', 'success');
        }
    }
}

// Get all periods
$periods = $conn->query("SELECT pp.*, u.first_name, u.last_name,
                         (SELECT COUNT(*) FROM payroll_records WHERE payroll_period_id = pp.id) as record_count
                         FROM payroll_periods pp
                         LEFT JOIN users u ON pp.created_by = u.id
                         ORDER BY pp.start_date DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Payroll Periods</h1>
                    <p class="text-muted">Manage payroll periods and computation</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#autoGenerateModal">
                        <i class="bi bi-magic"></i> Auto Generate Periods
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
                        <i class="bi bi-plus-circle"></i> Create Period
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Payroll Periods List</h5>
        </div>
        <div class="card-body">
            <?php if (count($periods) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Period Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Pay Date</th>
                            <th>Status</th>
                            <th>Records</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periods as $period): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($period['period_name']); ?></strong></td>
                            <td><?php echo formatDate($period['start_date']); ?></td>
                            <td><?php echo formatDate($period['end_date']); ?></td>
                            <td><?php echo formatDate($period['pay_date']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadge($period['status']); ?>">
                                    <?php echo ucfirst($period['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $period['record_count']; ?></td>
                            <td><?php echo htmlspecialchars($period['first_name'] . ' ' . $period['last_name']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo BASE_URL; ?>payroll/compute.php?period_id=<?php echo $period['id']; ?>" class="btn btn-primary" title="Compute">
                                        <i class="bi bi-calculator"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>payroll/reports.php?period_id=<?php echo $period['id']; ?>" class="btn btn-info" title="View Reports">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">No payroll periods found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Auto Generate Periods Modal -->
<div class="modal fade" id="autoGenerateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Auto Generate Payroll Periods</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This will create two periods for the selected month:<br>
                        <strong>Period 1:</strong> 5th - 20th (Pay Date: 21st)<br>
                        <strong>Period 2:</strong> 21st - 4th of next month (Pay Date: 5th)
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <select class="form-select" name="month" required>
                                <?php
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                $currentMonth = date('n');
                                foreach ($months as $num => $name):
                                ?>
                                <option value="<?php echo $num; ?>" <?php echo $num == $currentMonth ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <select class="form-select" name="year" required>
                                <?php
                                $currentYear = date('Y');
                                for ($y = $currentYear - 1; $y <= $currentYear + 2; $y++):
                                ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $currentYear ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="auto_generate" class="btn btn-success">Generate Periods</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Period Modal -->
<div class="modal fade" id="createPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Create Payroll Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Period Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="period_name" required placeholder="e.g., January 2024 - First Half">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pay Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="pay_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_period" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ================= INTERNAL PAYROLL PERIODS XML DATA =================
const payrollPeriodsXMLData = `<payroll_periods>
    <period id="1" name="christmas" status="draft">
        <start>2025-12-01</start>
        <end>2025-12-30</end>
        <pay_date>2025-12-31</pay_date>
    </period>
</payroll_periods>`;

// Parse Payroll Periods XML Data Function
function parsePayrollPeriodsXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(payrollPeriodsXMLData, 'text/xml');
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('Payroll Periods XML Parse Error:', parseError.textContent);
            return null;
        }
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing Payroll Periods XML:', error);
        return null;
    }
}

// Get Payroll Periods from XML
function getPayrollPeriodsFromXML() {
    const xmlDoc = parsePayrollPeriodsXMLData();
    if (!xmlDoc) return [];
    
    const periods = [];
    const periodNodes = xmlDoc.querySelectorAll('period');
    
    periodNodes.forEach(periodNode => {
        const period = {
            id: periodNode.getAttribute('id'),
            name: periodNode.getAttribute('name'),
            status: periodNode.getAttribute('status'),
            start: periodNode.querySelector('start')?.textContent || '',
            end: periodNode.querySelector('end')?.textContent || '',
            pay_date: periodNode.querySelector('pay_date')?.textContent || ''
        };
        periods.push(period);
    });
    
    return periods;
}

// Get Payroll Period by ID from XML
function getPayrollPeriodByIdFromXML(periodId) {
    const periods = getPayrollPeriodsFromXML();
    return periods.find(p => p.id === periodId);
}

console.log('XML Payroll Periods:', getPayrollPeriodsFromXML());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>