<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

Auth::requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Get user info
$userStmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$userStmt->bindValue(':id', $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->fetch();

// Get employee info if exists
$empStmt = $conn->prepare("SELECT * FROM employees WHERE user_id = :user_id");
$empStmt->bindValue(':user_id', $_SESSION['user_id']);
$empStmt->execute();
$employee = $empStmt->fetch();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all password fields.';
    } elseif ($newPassword != $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    } elseif (!password_verify($currentPassword, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $updateStmt->bindParam(':hash', $newHash);
        $updateStmt->bindValue(':id', $_SESSION['user_id']);
        
        if ($updateStmt->execute()) {
            logActivity('Password Changed', 'users', $_SESSION['user_id']);
            redirect('profile.php', 'Password changed successfully!', 'success');
        } else {
            $error = 'Failed to change password.';
        }
    }
}
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">My Profile</h1>
            <p class="text-muted">Manage your account information</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Username:</strong></div>
                        <div class="col-md-8"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Email:</strong></div>
                        <div class="col-md-8"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Full Name:</strong></div>
                        <div class="col-md-8"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Role:</strong></div>
                        <div class="col-md-8">
                            <span class="badge bg-<?php echo getRoleBadge($user['role']); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Last Login:</strong></div>
                        <div class="col-md-8"><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                            <small class="text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($employee): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employee Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Employee ID:</strong></div>
                        <div class="col-md-3"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                        <div class="col-md-3"><strong>Position:</strong></div>
                        <div class="col-md-3"><?php echo htmlspecialchars($employee['position']); ?></div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3"><strong>Department:</strong></div>
                        <div class="col-md-3"><?php echo htmlspecialchars($employee['department']); ?></div>
                        <div class="col-md-3"><strong>Base Salary:</strong></div>
                        <div class="col-md-3"><?php echo formatCurrency($employee['base_salary']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

