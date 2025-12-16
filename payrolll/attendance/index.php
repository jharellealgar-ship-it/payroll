<?php
$pageTitle = 'Attendance';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$employeeId = intval($_GET['employee_id'] ?? 0);
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-t'));

// Build query
$query = "SELECT a.*, e.employee_id, e.first_name, e.last_name, e.position 
          FROM attendance a 
          INNER JOIN employees e ON a.employee_id = e.id 
          WHERE a.attendance_date BETWEEN :start_date AND :end_date";

$params = [':start_date' => $startDate, ':end_date' => $endDate];

// If employee, only show their own records
if (Auth::hasRole('employee') && !Auth::hasRole('hr') && !Auth::hasRole('admin')) {
    // Get employee ID from user_id
    $empStmt = $conn->prepare("SELECT id FROM employees WHERE user_id = :user_id");
    $empStmt->bindValue(':user_id', $_SESSION['user_id']);
    $empStmt->execute();
    if ($empStmt->rowCount() > 0) {
        $empData = $empStmt->fetch();
        $query .= " AND a.employee_id = :current_employee_id";
        $params[':current_employee_id'] = $empData['id'];
    }
} elseif ($employeeId > 0) {
    $query .= " AND a.employee_id = :employee_id";
    $params[':employee_id'] = $employeeId;
}

$query .= " ORDER BY a.attendance_date DESC, e.last_name, e.first_name";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$attendance = $stmt->fetchAll();

// Get employees for filter (only for HR/Admin)
$employees = [];
if (Auth::hasRole('hr') || Auth::hasRole('admin')) {
    $empQuery = "SELECT id, employee_id, first_name, last_name FROM employees WHERE employment_status = 'active' ORDER BY last_name, first_name";
    $empStmt = $conn->prepare($empQuery);
    $empStmt->execute();
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Attendance Records</h1>
                    <p class="text-muted">View and manage employee attendance</p>
                </div>
                <?php if (Auth::hasRole('hr') || Auth::hasRole('admin')): ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <?php if (Auth::hasRole('hr') || Auth::hasRole('admin')): ?>
                <div class="col-md-4">
                    <label class="form-label">Employee</label>
                    <select class="form-select" name="employee_id">
                        <option value="0">All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo $employeeId == $emp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="col-md-4">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> Showing your attendance records only
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Attendance Records (<?php echo count($attendance); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($attendance) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Total Hours</th>
                            <th>Overtime</th>
                            <th>Late (min)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td><?php echo formatDate($record['attendance_date']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['employee_id']); ?></strong><br>
                                <small><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></small>
                            </td>
                            <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                            <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                            <td><?php echo number_format($record['total_hours'], 2); ?> hrs</td>
                            <td><?php echo number_format($record['overtime_hours'], 2); ?> hrs</td>
                            <td>
                                <?php if ($record['late_minutes'] > 0): ?>
                                <span class="text-danger"><?php echo $record['late_minutes']; ?></span>
                                <?php else: ?>
                                <span class="text-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadge($record['status']); ?>">
                                    <?php echo ucfirst(str_replace('-', ' ', $record['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">No attendance records found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- XML Data Display Section -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-file-code"></i> Internal XML Data (Attendance)
            </h5>
            <button class="btn btn-sm btn-outline-primary" onclick="toggleXMLDisplay()">
                <i class="bi bi-eye" id="xmlToggleIcon"></i> <span id="xmlToggleText">Show XML Data</span>
            </button>
        </div>
        <div class="card-body" id="xmlDataContainer" style="display: none;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This data is loaded from internal JavaScript (no external files).
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="xmlAttendanceTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Employee ID</th>
                            <th>Date</th>
                            <th>Total Hours</th>
                            <th>Overtime</th>
                            <th>Late (min)</th>
                        </tr>
                    </thead>
                    <tbody id="xmlAttendanceTableBody">
                        <!-- XML data will be populated here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <strong>Total XML Records:</strong> <span id="xmlRecordCount" class="badge bg-primary">0</span>
            </div>
        </div>
    </div>
</div>

<script>
// ================= INTERNAL ATTENDANCE XML DATA =================

const attendanceXMLData = `<attendance_records>
    <attendance id="1" employee_id="1" date="2025-12-13" late_minutes="213"/>
    <attendance id="2" employee_id="3" date="2025-12-18" total_hours="11.12" overtime="3.12"/>
    <attendance id="3" employee_id="1" date="2025-12-17" total_hours="9.00"/>
    <attendance id="4" employee_id="2" date="2025-12-17" total_hours="9.00"/>
    <attendance id="5" employee_id="3" date="2025-12-17" total_hours="8.00"/>
    <attendance id="6" employee_id="4" date="2025-12-17" total_hours="8.00"/>
    <attendance id="7" employee_id="5" date="2025-12-14"/>
    <attendance id="8" employee_id="6" date="2025-12-17"/>
    <attendance id="9" employee_id="7" date="2025-12-17"/>
    <attendance id="10" employee_id="8" date="2025-12-17"/>
    <attendance id="11" employee_id="9" date="2025-12-17"/>
    <attendance id="12" employee_id="10" date="2025-12-17" overtime="2.00"/>
    <attendance id="13" employee_id="11" date="2025-12-17"/>
</attendance_records>`;

// Parse Attendance XML Data Function
function parseAttendanceXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(attendanceXMLData, 'text/xml');
        
        // Check for parsing errors
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('Attendance XML Parse Error:', parseError.textContent);
            return null;
        }
        
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing Attendance XML:', error);
        return null;
    }
}

// Get Attendance Records from XML
function getAttendanceFromXML() {
    const xmlDoc = parseAttendanceXMLData();
    if (!xmlDoc) return [];
    
    const attendance = [];
    const attNodes = xmlDoc.querySelectorAll('attendance');
    
    attNodes.forEach(attNode => {
        const record = {
            id: attNode.getAttribute('id'),
            employee_id: attNode.getAttribute('employee_id'),
            date: attNode.getAttribute('date'),
            late_minutes: attNode.getAttribute('late_minutes') || '0',
            total_hours: attNode.getAttribute('total_hours') || '0',
            overtime: attNode.getAttribute('overtime') || '0'
        };
        attendance.push(record);
    });
    
    return attendance;
}

// Get Attendance by Employee ID from XML
function getAttendanceByEmployeeIdFromXML(employeeId) {
    const attendance = getAttendanceFromXML();
    return attendance.filter(a => a.employee_id === employeeId);
}

// Get Attendance by Date from XML
function getAttendanceByDateFromXML(date) {
    const attendance = getAttendanceFromXML();
    return attendance.filter(a => a.date === date);
}

// Get Attendance by Date Range from XML
function getAttendanceByDateRangeFromXML(startDate, endDate) {
    const attendance = getAttendanceFromXML();
    return attendance.filter(a => {
        const recordDate = new Date(a.date);
        const start = new Date(startDate);
        const end = new Date(endDate);
        return recordDate >= start && recordDate <= end;
    });
}

// Display XML Data in Frontend
function displayXMLAttendance() {
    const attendance = getAttendanceFromXML();
    const tbody = document.getElementById('xmlAttendanceTableBody');
    const countSpan = document.getElementById('xmlRecordCount');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (attendance.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No XML data found</td></tr>';
        if (countSpan) countSpan.textContent = '0';
        return;
    }
    
    attendance.forEach(att => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${att.id || ''}</td>
            <td><strong>${att.employee_id || ''}</strong></td>
            <td>${att.date || ''}</td>
            <td>${parseFloat(att.total_hours || 0).toFixed(2)} hrs</td>
            <td>${parseFloat(att.overtime || 0).toFixed(2)} hrs</td>
            <td>${att.late_minutes || '0'} min</td>
        `;
        tbody.appendChild(row);
    });
    
    if (countSpan) countSpan.textContent = attendance.length;
}

// Toggle XML Display
function toggleXMLDisplay() {
    const container = document.getElementById('xmlDataContainer');
    const icon = document.getElementById('xmlToggleIcon');
    const text = document.getElementById('xmlToggleText');
    
    if (container && container.style.display === 'none') {
        container.style.display = 'block';
        if (icon) icon.className = 'bi bi-eye-slash';
        if (text) text.textContent = 'Hide XML Data';
        displayXMLAttendance();
    } else if (container) {
        container.style.display = 'none';
        if (icon) icon.className = 'bi bi-eye';
        if (text) text.textContent = 'Show XML Data';
    }
}

// Load XML data on page load
document.addEventListener('DOMContentLoaded', function() {
    const attendance = getAttendanceFromXML();
    console.log('XML Attendance Records (Internal):', attendance);
    console.log('Total XML Attendance Records:', attendance.length);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>