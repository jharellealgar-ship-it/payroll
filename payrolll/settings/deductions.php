<?php
$pageTitle = 'Deduction Types';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name'] ?? '');
        $code = sanitize($_POST['code'] ?? '');
        $type = sanitize($_POST['type'] ?? 'fixed');
        $defaultValue = floatval($_POST['default_value'] ?? 0);
        $isGovernment = isset($_POST['is_government_mandated']) ? 1 : 0;
        
        if (!empty($name) && !empty($code)) {
            $query = "INSERT INTO deduction_types (name, code, type, default_value, is_government_mandated) 
                      VALUES (:name, :code, :type, :default_value, :is_government_mandated)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':default_value', $defaultValue);
            $stmt->bindParam(':is_government_mandated', $isGovernment);
            
            if ($stmt->execute()) {
                redirect('settings/deductions.php', 'Deduction type added successfully!', 'success');
            }
        }
    }
    
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Check if deduction type is being used
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM employee_deductions WHERE deduction_type_id = :id");
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['count'] > 0) {
                redirect('settings/deductions.php', 'Cannot delete deduction type that is currently in use!', 'error');
            }
            
            $query = "DELETE FROM deduction_types WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                redirect('settings/deductions.php', 'Deduction type deleted successfully!', 'success');
            } else {
                redirect('settings/deductions.php', 'Failed to delete deduction type!', 'error');
            }
        }
    }
}

// Get all deduction types
$deductions = $conn->query("SELECT * FROM deduction_types ORDER BY is_government_mandated DESC, name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Deduction Types</h1>
            <p class="text-muted">Manage deduction types and configurations</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Deduction Type</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" required style="text-transform: uppercase;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                                <option value="variable">Variable</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default Value</label>
                            <input type="number" class="form-control currency-input" name="default_value" step="0.01" min="0" value="0">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="is_government_mandated" id="is_government">
                            <label class="form-check-label" for="is_government">Government Mandated</label>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Deduction Type
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Deduction Types List</h5>
                </div>
                <div class="card-body">
                    <?php if (count($deductions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Default Value</th>
                                    <th>Government</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deductions as $ded): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ded['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($ded['code']); ?></code></td>
                                    <td><?php echo ucfirst($ded['type']); ?></td>
                                    <td><?php echo formatCurrency($ded['default_value']); ?></td>
                                    <td>
                                        <?php if ($ded['is_government_mandated']): ?>
                                        <span class="badge bg-info">Yes</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $ded['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $ded['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this deduction type? This action cannot be undone.');">
                                            <input type="hidden" name="id" value="<?php echo $ded['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm btn-delete" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">No deduction types found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

