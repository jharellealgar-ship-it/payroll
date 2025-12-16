<?php
$pageTitle = 'Incentive Types';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $name = sanitize($_POST['name'] ?? '');
    $code = sanitize($_POST['code'] ?? '');
    $type = sanitize($_POST['type'] ?? 'fixed');
    $defaultValue = floatval($_POST['default_value'] ?? 0);
    
    if (!empty($name) && !empty($code)) {
        $query = "INSERT INTO incentive_types (name, code, type, default_value) 
                  VALUES (:name, :code, :type, :default_value)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':default_value', $defaultValue);
        
        if ($stmt->execute()) {
            redirect('settings/incentives.php', 'Incentive type added successfully!', 'success');
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id > 0) {
        // Check if incentive type is being used
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM employee_incentives WHERE incentive_type_id = :id");
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($checkResult['count'] > 0) {
            redirect('settings/incentives.php', 'Cannot delete incentive type that is currently in use!', 'error');
        }
        
        $query = "DELETE FROM incentive_types WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            redirect('settings/incentives.php', 'Incentive type deleted successfully!', 'success');
        } else {
            redirect('settings/incentives.php', 'Failed to delete incentive type!', 'error');
        }
    }
}

// Get all incentive types
$incentives = $conn->query("SELECT * FROM incentive_types ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Incentive Types</h1>
            <p class="text-muted">Manage incentive types and configurations</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Incentive Type</h5>
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
                        <button type="submit" name="add" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Incentive Type
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Incentive Types List</h5>
                </div>
                <div class="card-body">
                    <?php if (count($incentives) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Default Value</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incentives as $inc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inc['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($inc['code']); ?></code></td>
                                    <td><?php echo ucfirst($inc['type']); ?></td>
                                    <td><?php echo formatCurrency($inc['default_value']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $inc['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $inc['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this incentive type? This action cannot be undone.');">
                                            <input type="hidden" name="id" value="<?php echo $inc['id']; ?>">
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
                    <p class="text-muted text-center">No incentive types found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

