<?php
$pageTitle = 'View Employee';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();

$database = new Database();
$conn = $database->getConnection();

$id = intval($_GET['id'] ?? 0);

$query = "SELECT e.*, u.username 
          FROM employees e 
          LEFT JOIN users u ON e.user_id = u.id 
          WHERE e.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    redirect('employees/index.php', 'Employee not found.', 'error');
}

require_once __DIR__ . '/../includes/header.php';

$employee = $stmt->fetch();
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Employee Details</h1>
                    <p class="text-muted"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></p>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>employees/edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="<?php echo BASE_URL; ?>employees/index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Employee ID:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Full Name:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['first_name'] . ' ' . ($employee['middle_name'] ? $employee['middle_name'] . ' ' : '') . $employee['last_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Email:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['email']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Phone:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['phone'] ?: '-'); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Address:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['address'] ?: '-'); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Date of Birth:</strong></div>
                        <div class="col-md-9"><?php echo $employee['date_of_birth'] ? formatDate($employee['date_of_birth']) : '-'; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Gender:</strong></div>
                        <div class="col-md-9"><?php echo ucfirst($employee['gender'] ?: '-'); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Marital Status:</strong></div>
                        <div class="col-md-9"><?php echo ucfirst($employee['marital_status'] ?: '-'); ?></div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Employment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Position:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['position']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Department:</strong></div>
                        <div class="col-md-9"><?php echo htmlspecialchars($employee['department']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Employment Type:</strong></div>
                        <div class="col-md-9"><?php echo ucfirst(str_replace('-', ' ', $employee['employment_type'])); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Employment Status:</strong></div>
                        <div class="col-md-9">
                            <span class="badge bg-<?php echo getStatusBadge($employee['employment_status']); ?>">
                                <?php echo ucfirst($employee['employment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Hire Date:</strong></div>
                        <div class="col-md-9"><?php echo formatDate($employee['hire_date']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Base Salary:</strong></div>
                        <div class="col-md-9"><?php echo formatCurrency($employee['base_salary']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Hourly Rate:</strong></div>
                        <div class="col-md-9"><?php echo $employee['hourly_rate'] ? formatCurrency($employee['hourly_rate']) : '-'; ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

