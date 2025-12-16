<?php
$pageTitle = 'Employees';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('hr');

$database = new Database();
$conn = $database->getConnection();

// Handle archive employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive_employee'])) {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    
    if ($employeeId > 0) {
        // Archive employee by setting status to 'terminated' instead of deleting
        // This preserves all historical data (attendance, payroll, leave requests, etc.)
        $archiveStmt = $conn->prepare("UPDATE employees SET employment_status = 'terminated', updated_at = NOW() WHERE id = :id");
        $archiveStmt->bindParam(':id', $employeeId);
        
        if ($archiveStmt->execute()) {
            logActivity('Employee Archived', 'employees', $employeeId);
            redirect('employees/index.php', 'Employee archived successfully! Historical data preserved.', 'success');
        } else {
            $error = 'Failed to archive employee.';
        }
    }
}

// Handle delete employee (WARNING: This will cascade delete all related records!)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_employee'])) {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    
    if ($employeeId > 0) {
        // Check if employee has related records
        $checkAttendance = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE employee_id = :id");
        $checkAttendance->bindParam(':id', $employeeId);
        $checkAttendance->execute();
        $attendanceCount = $checkAttendance->fetch()['count'];
        
        $checkPayroll = $conn->prepare("SELECT COUNT(*) as count FROM payroll_records WHERE employee_id = :id");
        $checkPayroll->bindParam(':id', $employeeId);
        $checkPayroll->execute();
        $payrollCount = $checkPayroll->fetch()['count'];
        
        // WARNING: This will CASCADE DELETE all related records:
        // - All attendance records
        // - All payroll records
        // - All leave requests
        // - All employee deductions
        // - All employee incentives
        // This action is IRREVERSIBLE!
        
        $deleteStmt = $conn->prepare("DELETE FROM employees WHERE id = :id");
        $deleteStmt->bindParam(':id', $employeeId);
        
        if ($deleteStmt->execute()) {
            logActivity('Employee Deleted (Cascade)', 'employees', $employeeId);
            redirect('employees/index.php', "Employee deleted. Also deleted: $attendanceCount attendance records, $payrollCount payroll records.", 'success');
        } else {
            $error = 'Failed to delete employee.';
        }
    }
}

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$department = sanitize($_GET['department'] ?? '');

// Build query
$query = "SELECT e.*, u.username 
          FROM employees e 
          LEFT JOIN users u ON e.user_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (e.employee_id LIKE :search1 OR e.first_name LIKE :search2 OR e.last_name LIKE :search3 OR e.email LIKE :search4)";
    $searchParam = "%$search%";
    $params[':search1'] = $searchParam;
    $params[':search2'] = $searchParam;
    $params[':search3'] = $searchParam;
    $params[':search4'] = $searchParam;
}

if (!empty($status)) {
    $query .= " AND e.employment_status = :status";
    $params[':status'] = $status;
}

if (!empty($department)) {
    $query .= " AND e.department = :department";
    $params[':department'] = $department;
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$employees = $stmt->fetchAll();

// Get unique departments for filter
$departments = $conn->query("SELECT DISTINCT department FROM employees ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Employee Management</h1>
                    <p class="text-muted">Manage employee information and records</p>
                </div>
                <a href="<?php echo BASE_URL; ?>employees/add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Employee
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Employee ID, Name, or Email">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="on-leave" <?php echo $status == 'on-leave' ? 'selected' : ''; ?>>On Leave</option>
                        <option value="suspended" <?php echo $status == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="terminated" <?php echo $status == 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select class="form-select" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department == $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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

    <!-- Employees Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Employee List (<?php echo count($employees); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($employees) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Base Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <?php 
                                $hasPhoto = false;
                                $photoPath = '';
                                $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
                                
                                if (!empty($emp['photo']) && file_exists(UPLOAD_DIR . 'employees/' . $emp['photo'])) {
                                    $photoPath = BASE_URL . 'uploads/employees/' . $emp['photo'];
                                    $hasPhoto = true;
                                }
                                ?>
                                <?php if ($hasPhoto): ?>
                                    <img src="<?php echo $photoPath; ?>" 
                                         alt="<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>" 
                                         class="rounded-circle" 
                                         style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">
                                <?php else: ?>
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold" 
                                         style="width: 50px; height: 50px; background-color: #6c757d; border: 2px solid #dee2e6; font-size: 16px;">
                                        <?php echo $initials; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($emp['employee_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td><?php echo formatCurrency($emp['base_salary']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadge($emp['employment_status']); ?>">
                                    <?php echo ucfirst($emp['employment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo BASE_URL; ?>employees/view.php?id=<?php echo $emp['id']; ?>" class="btn btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>employees/edit.php?id=<?php echo $emp['id']; ?>" class="btn btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to archive this employee?')">
                                        <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" name="archive_employee" class="btn btn-secondary btn-sm" title="Archive">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
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
                <p class="text-muted mt-3">No employees found.</p>
                <a href="<?php echo BASE_URL; ?>employees/add.php" class="btn btn-primary">Add First Employee</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- XML Data Display Section -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-file-code"></i>Data (Employees)
            </h5>
            <button class="btn btn-sm btn-outline-primary" onclick="toggleXMLDisplay()">
                <i class="bi bi-eye" id="xmlToggleIcon"></i> <span id="xmlToggleText">Show Data</span>
            </button>
        </div>
        <div class="card-body" id="xmlDataContainer" style="display: none;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This data is loaded!!!!
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="xmlEmployeesTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Employee #</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Base Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="xmlEmployeesTableBody">
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

const employeesXMLData = `<employees>
    <employee id="1" user_id="2" status="active">
        <employee_number>1</employee_number>
        <name>
            <first>BRYAN</first>
            <middle>A.</middle>
            <last>NIEVES</last>
        </name>
        <email>nievesbryan2004@gmail.com</email>
        <phone>0932134544</phone>
        <position>killer</position>
        <department>HEAD</department>
        <salary base="35000.00" hourly="0.00"/>
        <bank name="GCASH" account="21312312"/>
        <hire_date>2025-12-13</hire_date>
    </employee>
    <employee id="2" user_id="4" status="active">
        <employee_number>2</employee_number>
        <name>
            <first>nayrb</first>
            <middle>A.</middle>
            <last>NIEVES</last>
        </name>
        <salary base="35000.00" hourly="145.00"/>
    </employee>
    <employee id="3" user_id="3" status="active">
        <employee_number>3</employee_number>
        <name>
            <first>Constancio</first>
            <middle>Dominador</middle>
            <last>Magtagobtob</last>
        </name>
        <salary base="800000.00"/>
    </employee>
    <employee id="4" user_id="5" status="active">
        <employee_number>4</employee_number>
        <name>
            <first>Jharelle</first>
            <middle>Del Coro</middle>
            <last>Algar</last>
        </name>
        <salary base="25000.00"/>
    </employee>
    <employee id="5" user_id="6" status="active">
        <employee_number>5</employee_number>
        <name>
            <first>Rhafe</first>
            <middle>K.</middle>
            <last>Albinda</last>
        </name>
        <salary base="25000.00"/>
    </employee>
    <employee id="6" user_id="7" status="active">
        <employee_number>6</employee_number>
        <name>
            <first>Billy</first>
            <middle>A.</middle>
            <last>Nieves</last>
        </name>
        <salary base="25000.00"/>
    </employee>
    <employee id="7" user_id="8" status="active">
        <employee_number>7</employee_number>
        <name>
            <first>Marybel</first>
            <middle>A.</middle>
            <last>Nieves</last>
        </name>
        <salary base="20000.00"/>
    </employee>
    <employee id="8" user_id="9" status="active">
        <employee_number>8</employee_number>
        <name>
            <first>Babelyn</first>
            <middle>A.</middle>
            <last>Nieves</last>
        </name>
        <salary base="40000.00"/>
    </employee>
    <employee id="9" user_id="10" status="active">
        <employee_number>9</employee_number>
        <name>
            <first>Valerio</first>
            <middle>A.</middle>
            <last>Nieves</last>
        </name>
        <salary base="45000.00"/>
    </employee>
    <employee id="10" user_id="11" status="active">
        <employee_number>10</employee_number>
        <name>
            <first>Ruperta</first>
            <middle>Nieves</middle>
            <last>Cimafranca</last>
        </name>
        <salary base="45000.00"/>
    </employee>
    <employee id="11" user_id="12" status="active">
        <employee_number>11</employee_number>
        <name>
            <first>Melisa</first>
            <middle>Nieves</middle>
            <last>Calago</last>
        </name>
        <salary base="45000.00"/>
    </employee>
</employees>`;

// Parse Employees XML Data Function
function parseEmployeesXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(employeesXMLData, 'text/xml');
        
        // Check for parsing errors
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('Employees XML Parse Error:', parseError.textContent);
            return null;
        }
        
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing Employees XML:', error);
        return null;
    }
}

// Get Employees from XML
function getEmployeesFromXML() {
    const xmlDoc = parseEmployeesXMLData();
    if (!xmlDoc) return [];
    
    const employees = [];
    const empNodes = xmlDoc.querySelectorAll('employee');
    
    empNodes.forEach(empNode => {
        const nameNode = empNode.querySelector('name');
        const employee = {
            id: empNode.getAttribute('id'),
            user_id: empNode.getAttribute('user_id'),
            status: empNode.getAttribute('status'),
            employee_number: empNode.querySelector('employee_number')?.textContent || '',
            first_name: nameNode?.querySelector('first')?.textContent || '',
            middle_name: nameNode?.querySelector('middle')?.textContent || '',
            last_name: nameNode?.querySelector('last')?.textContent || '',
            email: empNode.querySelector('email')?.textContent || '',
            phone: empNode.querySelector('phone')?.textContent || '',
            position: empNode.querySelector('position')?.textContent || '',
            department: empNode.querySelector('department')?.textContent || '',
            base_salary: empNode.querySelector('salary')?.getAttribute('base') || '0',
            hourly_rate: empNode.querySelector('salary')?.getAttribute('hourly') || '0',
            bank_name: empNode.querySelector('bank')?.getAttribute('name') || '',
            bank_account: empNode.querySelector('bank')?.getAttribute('account') || '',
            hire_date: empNode.querySelector('hire_date')?.textContent || ''
        };
        employees.push(employee);
    });
    
    return employees;
}

// Get Employee by ID from XML
function getEmployeeByIdFromXML(employeeId) {
    const employees = getEmployeesFromXML();
    return employees.find(e => e.id === employeeId);
}

// Get Employee by User ID from XML
function getEmployeeByUserIdFromXML(userId) {
    const employees = getEmployeesFromXML();
    return employees.find(e => e.user_id === userId);
}

// Get Employees by Status from XML
function getEmployeesByStatusFromXML(status) {
    const employees = getEmployeesFromXML();
    return employees.filter(e => e.status === status);
}

// Display XML Data in Frontend
function displayXMLEmployees() {
    const employees = getEmployeesFromXML();
    const tbody = document.getElementById('xmlEmployeesTableBody');
    const countSpan = document.getElementById('xmlRecordCount');
    
    if (!tbody) return;
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No XML data found</td></tr>';
        if (countSpan) countSpan.textContent = '0';
        return;
    }
    
    // Populate table with XML data
    employees.forEach(emp => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${emp.id || ''}</td>
            <td><strong>${emp.employee_number || ''}</strong></td>
            <td>${(emp.first_name || '') + ' ' + (emp.middle_name || '') + ' ' + (emp.last_name || '')}</td>
            <td>${emp.email || ''}</td>
            <td>${emp.position || ''}</td>
            <td>${emp.department || ''}</td>
            <td>₱${parseFloat(emp.base_salary || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td><span class="badge bg-${emp.status === 'active' ? 'success' : 'secondary'}">${emp.status || ''}</span></td>
        `;
        tbody.appendChild(row);
    });
    
    if (countSpan) countSpan.textContent = employees.length;
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
        displayXMLEmployees(); // Load and display data when showing
    } else if (container) {
        container.style.display = 'none';
        if (icon) icon.className = 'bi bi-eye';
        if (text) text.textContent = 'Show XML Data';
    }
}

// Load XML data on page load and log to console
document.addEventListener('DOMContentLoaded', function() {
    const employees = getEmployeesFromXML();
    console.log('XML Employees (Internal):', employees);
    console.log('Total XML Employees:', employees.length);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>