<?php
$pageTitle = 'Payroll Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('accountant');

$database = new Database();
$conn = $database->getConnection();

$periodId = intval($_GET['period_id'] ?? 0);

if ($periodId > 0) {
    // Get period details
    $periodStmt = $conn->prepare("SELECT * FROM payroll_periods WHERE id = :id");
    $periodStmt->bindParam(':id', $periodId);
    $periodStmt->execute();
    $period = $periodStmt->fetch();
    
    // Get payroll records
    $query = "SELECT pr.*, e.employee_id, e.first_name, e.last_name, e.position, e.department
              FROM payroll_records pr
              INNER JOIN employees e ON pr.employee_id = e.id
              WHERE pr.payroll_period_id = :period_id
              ORDER BY e.last_name, e.first_name";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':period_id', $periodId);
    $stmt->execute();
    $records = $stmt->fetchAll();
    
    // Calculate totals
    $totals = [
        'gross' => 0,
        'deductions' => 0,
        'incentives' => 0,
        'net' => 0
    ];
    foreach ($records as $record) {
        $totals['gross'] += $record['gross_salary'];
        $totals['deductions'] += $record['total_deductions'];
        $totals['incentives'] += $record['total_incentives'];
        $totals['net'] += $record['net_pay'];
    }
} else {
    // Get all periods
    $periods = $conn->query("SELECT * FROM payroll_periods ORDER BY start_date DESC")->fetchAll();
}
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Payroll Reports</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>payroll/periods.php">Payroll Periods</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($periodId > 0 && isset($period)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($period['period_name']); ?></h5>
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Period:</strong><br>
                    <?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Pay Date:</strong><br>
                    <?php echo formatDate($period['pay_date']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    <span class="badge bg-<?php echo getStatusBadge($period['status']); ?>">
                        <?php echo ucfirst($period['status']); ?>
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>Total Employees:</strong><br>
                    <?php echo count($records); ?>
                </div>
            </div>
            
            <?php if (count($records) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Day Salary</th>
                            <th>Overtime Pay</th>
                            <th>Gross Salary</th>
                            <th>Deductions</th>
                            <th>Incentives</th>
                            <th>Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['position']); ?></td>
                            <td><?php echo formatCurrency($record['basic_salary']); ?></td>
                            <td><?php echo formatCurrency($record['overtime_pay']); ?></td>
                            <td><?php echo formatCurrency($record['gross_salary']); ?></td>
                            <td><?php echo formatCurrency($record['total_deductions']); ?></td>
                            <td><?php echo formatCurrency($record['total_incentives']); ?></td>
                            <td><strong><?php echo formatCurrency($record['net_pay']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="5" class="text-end">TOTALS:</th>
                            <th><?php echo formatCurrency($totals['gross']); ?></th>
                            <th><?php echo formatCurrency($totals['deductions']); ?></th>
                            <th><?php echo formatCurrency($totals['incentives']); ?></th>
                            <th><strong><?php echo formatCurrency($totals['net']); ?></strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No payroll records found for this period.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Select Payroll Period</h5>
        </div>
        <div class="card-body">
            <div class="list-group">
                <?php foreach ($periods as $p): ?>
                <a href="?period_id=<?php echo $p['id']; ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($p['period_name']); ?></h6>
                            <small><?php echo formatDate($p['start_date']); ?> - <?php echo formatDate($p['end_date']); ?></small>
                        </div>
                        <span class="badge bg-<?php echo getStatusBadge($p['status']); ?>">
                            <?php echo ucfirst($p['status']); ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// ================= INTERNAL PAYROLL RECORDS XML DATA =================
const payrollRecordsXMLData = `<payroll_records>
    <record id="1" employee_id="3" period_id="1">
        <gross>400000</gross>
        <deductions total="144000"/>
        <net>256000</net>
    </record>
    <record id="2" employee_id="1" period_id="1">
        <gross>17500</gross>
        <deductions total="8430"/>
        <net>9070</net>
    </record>
    <record id="3" employee_id="2" period_id="1">
        <gross>17500</gross>
        <deductions total="6300"/>
        <net>11200</net>
    </record>
</payroll_records>`;

// Parse Payroll Records XML Data Function
function parsePayrollRecordsXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(payrollRecordsXMLData, 'text/xml');
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('Payroll Records XML Parse Error:', parseError.textContent);
            return null;
        }
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing Payroll Records XML:', error);
        return null;
    }
}

// Get Payroll Records from XML
function getPayrollRecordsFromXML() {
    const xmlDoc = parsePayrollRecordsXMLData();
    if (!xmlDoc) return [];
    
    const records = [];
    const recordNodes = xmlDoc.querySelectorAll('record');
    
    recordNodes.forEach(recordNode => {
        const record = {
            id: recordNode.getAttribute('id'),
            employee_id: recordNode.getAttribute('employee_id'),
            period_id: recordNode.getAttribute('period_id'),
            gross: recordNode.querySelector('gross')?.textContent || '0',
            deductions_total: recordNode.querySelector('deductions')?.getAttribute('total') || '0',
            net: recordNode.querySelector('net')?.textContent || '0'
        };
        records.push(record);
    });
    
    return records;
}

// Get Payroll Records by Employee ID from XML
function getPayrollRecordsByEmployeeIdFromXML(employeeId) {
    const records = getPayrollRecordsFromXML();
    return records.filter(r => r.employee_id === employeeId);
}

// Get Payroll Records by Period ID from XML
function getPayrollRecordsByPeriodIdFromXML(periodId) {
    const records = getPayrollRecordsFromXML();
    return records.filter(r => r.period_id === periodId);
}

console.log('XML Payroll Records:', getPayrollRecordsFromXML());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>