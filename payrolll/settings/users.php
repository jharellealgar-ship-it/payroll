<?php
$pageTitle = 'System Users';
$useDarkTheme = true; // Enable dark theme for this page
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('admin');

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

// Handle update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role = sanitize($_POST['role'] ?? 'employee');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($userId) || empty($username) || empty($email)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists (excluding current user)
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->bindParam(':id', $userId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Update user
            $updateStmt = $conn->prepare("UPDATE users SET username = :username, email = :email, role = :role, is_active = :is_active, updated_at = NOW() WHERE id = :id");
            $updateStmt->bindParam(':id', $userId);
            $updateStmt->bindParam(':username', $username);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->bindParam(':role', $role);
            $updateStmt->bindParam(':is_active', $isActive);
            
            if ($updateStmt->execute()) {
                logActivity('User Account Updated', 'users', $userId);
                redirect('settings/users.php', 'User account updated successfully!', 'success');
            } else {
                $error = 'Failed to update user account.';
            }
        }
    }
}

// Handle toggle user status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    
    if ($userId > 0) {
        // Prevent deactivating yourself
        if ($userId == $_SESSION['user_id'] && $newStatus == 0) {
            $error = 'You cannot deactivate your own account.';
        } else {
            $toggleStmt = $conn->prepare("UPDATE users SET is_active = :status, updated_at = NOW() WHERE id = :id");
            $toggleStmt->bindParam(':id', $userId);
            $toggleStmt->bindParam(':status', $newStatus);
            
            if ($toggleStmt->execute()) {
                $statusText = $newStatus ? 'activated' : 'deactivated';
                logActivity('User Account ' . ucfirst($statusText), 'users', $userId);
                redirect('settings/users.php', 'User account ' . $statusText . ' successfully!', 'success');
            } else {
                $error = 'Failed to update user status.';
            }
        }
    } else {
        $error = 'Invalid user ID.';
    }
}

// Handle create user account for employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'employee');
    
    if (empty($employeeId) || empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    } else {
        // Check if employee exists
        $empStmt = $conn->prepare("SELECT id, email, first_name, last_name, user_id FROM employees WHERE id = :id");
        $empStmt->bindParam(':id', $employeeId);
        $empStmt->execute();
        
        if ($empStmt->rowCount() == 0) {
            $error = 'Employee not found.';
        } else {
            $employee = $empStmt->fetch();
            
            // Check if employee already has a user account
            if ($employee['user_id']) {
                $error = 'This employee already has a user account.';
            } else {
                // Check if username already exists
                $userCheck = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
                $userCheck->bindParam(':username', $username);
                $userCheck->bindParam(':email', $employee['email']);
                $userCheck->execute();
                
                if ($userCheck->rowCount() > 0) {
                    $error = 'Username or email already exists.';
                } else {
                    // Create user account
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $insertUser = $conn->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name) 
                                                   VALUES (:username, :email, :password_hash, :role, :first_name, :last_name)");
                    $insertUser->bindParam(':username', $username);
                    $insertUser->bindParam(':email', $employee['email']);
                    $insertUser->bindParam(':password_hash', $passwordHash);
                    $insertUser->bindParam(':role', $role);
                    $insertUser->bindParam(':first_name', $employee['first_name']);
                    $insertUser->bindParam(':last_name', $employee['last_name']);
                    
                    if ($insertUser->execute()) {
                        $newUserId = $conn->lastInsertId();
                        
                        // Link user to employee
                        $updateEmp = $conn->prepare("UPDATE employees SET user_id = :user_id WHERE id = :emp_id");
                        $updateEmp->bindParam(':user_id', $newUserId);
                        $updateEmp->bindParam(':emp_id', $employeeId);
                        $updateEmp->execute();
                        
                        logActivity('User Account Created for Employee', 'users', $newUserId);
                        redirect('settings/users.php', 'User account created successfully! Employee can now login.', 'success');
                    } else {
                        $error = 'Failed to create user account.';
                    }
                }
            }
        }
    }
}

// Get all users
$users = $conn->query("SELECT u.*, e.employee_id, e.position 
                       FROM users u 
                       LEFT JOIN employees e ON u.id = e.user_id 
                       ORDER BY u.created_at DESC")->fetchAll();

// Get employees without user accounts
$employeesWithoutUsers = $conn->query("SELECT id, employee_id, first_name, last_name, email, position 
                                       FROM employees 
                                       WHERE user_id IS NULL AND employment_status = 'active'
                                       ORDER BY last_name, first_name")->fetchAll();

// Handle XML export
// Get statistics
$totalUsers = count($users);
$activeUsers = count(array_filter($users, function($u) { return $u['is_active']; }));
$inactiveUsers = $totalUsers - $activeUsers;
$usersWithEmployees = count(array_filter($users, function($u) { return !empty($u['employee_id']); }));
?>
<style>
/* =========================
   SYSTEM USERS – DARK THEME
   ========================= */

body {
    background-color: #0f172a;
    color: #e5e7eb;
}

h1, h3, h5 {
    color: #ffffff;
}

.text-muted {
    color: #9ca3af !important;
}

/* Statistics Cards */
.stats-card {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6);
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    border-color: #475569;
}

.stats-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0.5rem 0;
}

.stats-label {
    color: #9ca3af;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Cards */
.card {
    background-color: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-bottom: 1px solid #334155;
    padding: 1.25rem 1.5rem;
    border-radius: 12px 12px 0 0;
}

.card-header h5 {
    color: #ffffff;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Forms */
.form-label {
    color: #d1d5db;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control,
.form-select {
    background-color: #0f172a;
    color: #ffffff;
    border: 1px solid #334155;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    transition: all 0.2s ease;
}

.form-control:focus,
.form-select:focus {
    background-color: #0f172a;
    color: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

.form-control::placeholder {
    color: #64748b;
}

.form-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%23ffffff' d='M8 11L3 6h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
}

select.form-select option {
    background-color: #1e293b;
    color: #ffffff;
}

/* Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.625rem 1.25rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    color: #ffffff;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.btn-group-sm .btn {
    border-radius: 6px;
}

/* Alerts */
.alert {
    border-radius: 10px;
    border: none;
    padding: 1rem 1.25rem;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.15);
    border-left: 4px solid #dc3545;
    color: #fca5a5;
}

.alert-success {
    background-color: rgba(34, 197, 94, 0.15);
    border-left: 4px solid #22c55e;
    color: #86efac;
}

/* Tables */
.table {
    color: #e5e7eb;
    margin-bottom: 0;
}

.table thead {
    background-color: #0f172a;
}

.table thead th {
    border-bottom: 2px solid #334155;
    color: #ffffff;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 1rem;
}

.table tbody tr {
    border-bottom: 1px solid #334155;
    transition: all 0.2s ease;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.1);
    transform: scale(1.01);
}

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
}

/* Badges */
.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    border-radius: 6px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.bg-success {
    background-color: #22c55e !important;
    color: #ffffff !important;
}

.bg-danger {
    background-color: #ef4444 !important;
    color: #ffffff !important;
}

.bg-primary {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
}

.bg-warning {
    background-color: #f59e0b !important;
    color: #000000 !important;
}

.bg-info {
    background-color: #06b6d4 !important;
    color: #000000 !important;
}

.bg-secondary {
    background-color: #64748b !important;
    color: #ffffff !important;
}

/* Action Buttons */
.action-buttons .btn {
    margin: 0 2px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Small text */
small {
    color: #9ca3af;
    font-size: 0.875rem;
}

/* Breadcrumb */
.breadcrumb {
    background-color: transparent;
    padding: 0;
    margin: 0;
}

.breadcrumb-item a {
    color: #9ca3af;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #3b82f6;
}

.breadcrumb-item.active {
    color: #ffffff;
}

/* User Avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 600;
    margin-right: 0.75rem;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="h3 mb-2">
                        <i class="bi bi-people-fill text-primary"></i> System Users
                    </h1>
                    <p class="text-muted mb-0">Manage user accounts and access permissions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Error/Success Messages -->
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-primary">
                    <i class="bi bi-people text-white"></i>
                </div>
                <div class="stats-number"><?php echo $totalUsers; ?></div>
                <div class="stats-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-success">
                    <i class="bi bi-check-circle text-white"></i>
                </div>
                <div class="stats-number"><?php echo $activeUsers; ?></div>
                <div class="stats-label">Active Users</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-danger">
                    <i class="bi bi-x-circle text-white"></i>
                </div>
                <div class="stats-number"><?php echo $inactiveUsers; ?></div>
                <div class="stats-label">Inactive Users</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-icon bg-info">
                    <i class="bi bi-person-badge text-white"></i>
                </div>
                <div class="stats-number"><?php echo $usersWithEmployees; ?></div>
                <div class="stats-label">Linked Employees</div>
            </div>
        </div>
    </div>

    

        <!-- Users List -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i> All System Users
                    </h5>
                    <span class="badge bg-primary"><?php echo count($users); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Employee ID</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="bi bi-envelope me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getRoleBadge($user['role']); ?>">
                                            <i class="bi bi-<?php 
                                                echo $user['role'] == 'admin' ? 'shield-check' : 
                                                    ($user['role'] == 'hr' ? 'briefcase' : 
                                                    ($user['role'] == 'accountant' ? 'calculator' : 'person')); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['employee_id']): ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-id-card me-1"></i>
                                            <?php echo htmlspecialchars($user['employee_id']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                            <i class="bi bi-<?php echo $user['is_active'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if ($user['last_login']): ?>
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo formatDateTime($user['last_login']); ?>
                                            <?php else: ?>
                                            <i class="bi bi-dash-circle me-1"></i>Never
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm action-buttons">
                                            <button type="button" class="btn btn-outline-primary" title="View Details" 
                                                    data-user='<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                                                    onclick="viewUser(JSON.parse(this.getAttribute('data-user')))">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" title="Edit User" 
                                                    data-user='<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                                                    onclick="editUser(JSON.parse(this.getAttribute('data-user')))">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    title="Cannot deactivate your own account" disabled>
                                                <i class="bi bi-lock"></i>
                                            </button>
                                            <?php else: ?>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirmStatusChange(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>, '<?php echo htmlspecialchars($user['username']); ?>');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" name="toggle_status" 
                                                        class="btn btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?> btn-sm" 
                                                        title="<?php echo $user['is_active'] ? 'Deactivate User' : 'Activate User'; ?>">
                                                    <i class="bi bi-<?php echo $user['is_active'] ? 'x-circle' : 'check-circle'; ?>"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="mb-0">No users found in the system.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background-color: #1e293b; border: 1px solid #334155;">
            <div class="modal-header" style="border-bottom: 1px solid #334155;">
                <h5 class="modal-title text-white" id="viewUserModalLabel">
                    <i class="bi bi-person-circle me-2"></i>User Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer" style="border-top: 1px solid #334155;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #1e293b; border: 1px solid #334155;">
            <div class="modal-header" style="border-bottom: 1px solid #334155;">
                <h5 class="modal-title text-white" id="editUserModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-person me-1"></i> Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-envelope me-1"></i> Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-shield-check me-1"></i> Role
                        </label>
                        <select class="form-select" name="role" id="edit_role">
                            <option value="employee">Employee</option>
                            <option value="hr">HR Manager</option>
                            <option value="accountant">Accountant</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label text-white" for="edit_is_active">
                                Active Account
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #334155;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// View User Function
function viewUser(user) {
    const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    const content = document.getElementById('viewUserContent');
    
    const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
    const created = user.created_at ? new Date(user.created_at).toLocaleString() : 'N/A';
    const updated = user.updated_at ? new Date(user.updated_at).toLocaleString() : 'N/A';
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-person me-1"></i> Username:</strong>
                <p class="text-white mb-0">${user.username || 'N/A'}</p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-envelope me-1"></i> Email:</strong>
                <p class="text-white mb-0">${user.email || 'N/A'}</p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-person-badge me-1"></i> Full Name:</strong>
                <p class="text-white mb-0">${(user.first_name || '') + ' ' + (user.last_name || '')}</p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-shield-check me-1"></i> Role:</strong>
                <p class="mb-0"><span class="badge bg-primary">${user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'N/A'}</span></p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-id-card me-1"></i> Employee ID:</strong>
                <p class="text-white mb-0">${user.employee_id || '<span class="text-muted">Not linked</span>'}</p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-${user.is_active ? 'check-circle' : 'x-circle'} me-1"></i> Status:</strong>
                <p class="mb-0"><span class="badge bg-${user.is_active ? 'success' : 'danger'}">${user.is_active ? 'Active' : 'Inactive'}</span></p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-clock me-1"></i> Last Login:</strong>
                <p class="text-white mb-0">${lastLogin}</p>
            </div>
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-calendar-plus me-1"></i> Created At:</strong>
                <p class="text-white mb-0">${created}</p>
            </div>
            ${user.position ? `
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-briefcase me-1"></i> Position:</strong>
                <p class="text-white mb-0">${user.position}</p>
            </div>
            ` : ''}
            ${user.updated_at ? `
            <div class="col-md-6 mb-3">
                <strong class="text-muted d-block mb-2"><i class="bi bi-calendar-check me-1"></i> Last Updated:</strong>
                <p class="text-white mb-0">${updated}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    modal.show();
}

// Edit User Function
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role').value = user.role || 'employee';
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

// Confirm Status Change Function
function confirmStatusChange(userId, willActivate, username) {
    const action = willActivate ? 'activate' : 'deactivate';
    const message = `Are you sure you want to ${action} the user account "${username}"?\n\n` +
                   (willActivate 
                       ? 'This will allow the user to login to the system again.' 
                       : 'This will prevent the user from logging in to the system.');
    
    return confirm(message);
}

// ================= INTERNAL XML DATA =================
// Embedded XML data from payr.xml (Internal JavaScript)
const payrollXMLData = `<payroll_management>
<users>
    <user id="1" role="admin" active="1">
        <username>admin</username>
        <email>admin@payroll.com</email>
        <first_name>System</first_name>
        <last_name>Administrator</last_name>
        <last_login>2025-12-14 23:32:06</last_login>
        <created_at>2025-12-13 04:28:43</created_at>
    </user>
    <user id="2" role="employee" active="1">
        <username>bryan</username>
        <email>nievesbryan2004@gmail.com</email>
        <first_name>BRYAN</first_name>
        <last_name>NIEVES</last_name>
        <last_login>2025-12-13 12:35:39</last_login>
    </user>
    <user id="3" role="employee" active="1">
        <username>constancio</username>
        <email>adsas@gmail.com</email>
        <first_name>Constancio</first_name>
        <last_name>Magtagobtob</last_name>
    </user>
    <user id="4" role="hr" active="1">
        <username>hr</username>
        <email>nievesbryan@gmail.com</email>
        <first_name>nayrb</first_name>
        <last_name>NIEVES</last_name>
    </user>
    <user id="5" role="accountant" active="1">
        <username>jharelle</username>
        <email>joke@gmail.com</email>
        <first_name>Jharelle</first_name>
        <last_name>Algar</last_name>
    </user>
</users>
<employees>
    <employee id="1" user_id="2" status="active">
        <employee_number>1</employee_number>
        <name><first>BRYAN</first><middle>A.</middle><last>NIEVES</last></name>
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
        <name><first>nayrb</first><middle>A.</middle><last>NIEVES</last></name>
        <salary base="35000.00" hourly="145.00"/>
    </employee>
    <employee id="3" user_id="3" status="active">
        <employee_number>3</employee_number>
        <name><first>Constancio</first><middle>Dominador</middle><last>Magtagobtob</last></name>
        <salary base="800000.00"/>
    </employee>
    <employee id="4" user_id="5" status="active">
        <employee_number>4</employee_number>
        <name><first>Jharelle</first><middle>Del Coro</middle><last>Algar</last></name>
        <salary base="25000.00"/>
    </employee>
    <employee id="5" user_id="6" status="active">
        <employee_number>5</employee_number>
        <name><first>Rhafe</first><middle>K.</middle><last>Albinda</last></name>
        <salary base="25000.00"/>
    </employee>
    <employee id="6" user_id="7" status="active">
        <employee_number>6</employee_number>
        <name><first>Billy</first><middle>A.</middle><last>Nieves</last></name>
        <salary base="25000.00"/>
    </employee>
    <employee id="7" user_id="8" status="active">
        <employee_number>7</employee_number>
        <name><first>Marybel</first><middle>A.</middle><last>Nieves</last></name>
        <salary base="20000.00"/>
    </employee>
    <employee id="8" user_id="9" status="active">
        <employee_number>8</employee_number>
        <name><first>Babelyn</first><middle>A.</middle><last>Nieves</last></name>
        <salary base="40000.00"/>
    </employee>
    <employee id="9" user_id="10" status="active">
        <employee_number>9</employee_number>
        <name><first>Valerio</first><middle>A.</middle><last>Nieves</last></name>
        <salary base="45000.00"/>
    </employee>
    <employee id="10" user_id="11" status="active">
        <employee_number>10</employee_number>
        <name><first>Ruperta</first><middle>Nieves</middle><last>Cimafranca</last></name>
        <salary base="45000.00"/>
    </employee>
    <employee id="11" user_id="12" status="active">
        <employee_number>11</employee_number>
        <name><first>Melisa</first><middle>Nieves</middle><last>Calago</last></name>
        <salary base="45000.00"/>
    </employee>
</employees>
<attendance_records>
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
</attendance_records>
<leave_requests>
    <leave id="1" employee_id="1" type="sick" status="approved">
        <start>2025-12-13</start>
        <end>2025-12-14</end>
        <days>2</days>
    </leave>
    <leave id="2" employee_id="3" type="vacation" status="approved">
        <start>2025-12-24</start>
        <end>2025-12-26</end>
        <days>3</days>
    </leave>
</leave_requests>
<payroll_periods>
    <period id="1" name="christmas" status="draft">
        <start>2025-12-01</start>
        <end>2025-12-30</end>
        <pay_date>2025-12-31</pay_date>
    </period>
</payroll_periods>
<payroll_records>
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
</payroll_records>
<system_settings>
    <setting key="regular_hours_per_day">8</setting>
    <setting key="overtime_rate_multiplier">1.25</setting>
    <setting key="tax_rate">0.20</setting>
    <setting key="sss_rate">0.11</setting>
    <setting key="philhealth_rate">0.03</setting>
    <setting key="pagibig_rate">0.02</setting>
    <setting key="late_penalty_per_minute">10</setting>
</system_settings>
</payroll_management>`;

// ================= INTERNAL USERS TEMPLATE XML =================
// Embedded users template XML (Internal JavaScript)
const usersTemplateXML = `<users>
    <export_date>2025-12-15 00:00:00</export_date>
    <total_users>0</total_users>
    
    <!-- User Template - Copy this block for each user -->
    <user>
        <username>johndoe</username>
        <email>john.doe@example.com</email>
        <password>SecurePassword123</password>
        <first_name>John</first_name>
        <last_name>Doe</last_name>
        <role>employee</role>
        <employee_id>EMP001</employee_id>
        <is_active>1</is_active>
    </user>
    
    <!-- Example Users -->
    <user>
        <username>jane_smith</username>
        <email>jane.smith@example.com</email>
        <password>Password123</password>
        <first_name>Jane</first_name>
        <last_name>Smith</last_name>
        <role>hr</role>
        <employee_id>EMP002</employee_id>
        <is_active>1</is_active>
    </user>
    
    <user>
        <username>accountant1</username>
        <email>accountant@example.com</email>
        <password>Accountant123</password>
        <first_name>Accountant</first_name>
        <last_name>User</last_name>
        <role>accountant</role>
        <employee_id>EMP003</employee_id>
        <is_active>1</is_active>
    </user>
</users>`;

// Parse Users Template XML Function
function parseUsersTemplateXML() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(usersTemplateXML, 'text/xml');
        
        // Check for parsing errors
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('Users Template XML Parse Error:', parseError.textContent);
            return null;
        }
        
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing Users Template XML:', error);
        return null;
    }
}

// Get Users from Template XML
function getUsersFromTemplateXML() {
    const xmlDoc = parseUsersTemplateXML();
    if (!xmlDoc) return [];
    
    const users = [];
    const userNodes = xmlDoc.querySelectorAll('user');
    
    userNodes.forEach(userNode => {
        const user = {
            username: userNode.querySelector('username')?.textContent || '',
            email: userNode.querySelector('email')?.textContent || '',
            password: userNode.querySelector('password')?.textContent || '',
            first_name: userNode.querySelector('first_name')?.textContent || '',
            last_name: userNode.querySelector('last_name')?.textContent || '',
            role: userNode.querySelector('role')?.textContent || 'employee',
            employee_id: userNode.querySelector('employee_id')?.textContent || '',
            is_active: userNode.querySelector('is_active')?.textContent === '1'
        };
        users.push(user);
    });
    
    return users;
}

// Get Template XML as String
function getUsersTemplateXMLString() {
    return usersTemplateXML;
}

// Parse XML Data Function
function parseXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(payrollXMLData, 'text/xml');
        
        // Check for parsing errors
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('XML Parse Error:', parseError.textContent);
            return null;
        }
        
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing XML:', error);
        return null;
    }
}

// Get Users from XML
function getUsersFromXML() {
    const xmlDoc = parseXMLData();
    if (!xmlDoc) return [];
    
    const users = [];
    const userNodes = xmlDoc.querySelectorAll('user');
    
    userNodes.forEach(userNode => {
        const user = {
            id: userNode.getAttribute('id'),
            role: userNode.getAttribute('role'),
            active: userNode.getAttribute('active') === '1',
            username: userNode.querySelector('username')?.textContent || '',
            email: userNode.querySelector('email')?.textContent || '',
            first_name: userNode.querySelector('first_name')?.textContent || '',
            last_name: userNode.querySelector('last_name')?.textContent || '',
            last_login: userNode.querySelector('last_login')?.textContent || null,
            created_at: userNode.querySelector('created_at')?.textContent || null
        };
        users.push(user);
    });
    
    return users;
}

// Get Employees from XML
function getEmployeesFromXML() {
    const xmlDoc = parseXMLData();
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

// Get User by ID from XML
function getUserByIdFromXML(userId) {
    const users = getUsersFromXML();
    return users.find(u => u.id === userId);
}

// Get Employee by User ID from XML
function getEmployeeByUserIdFromXML(userId) {
    const employees = getEmployeesFromXML();
    return employees.find(e => e.user_id === userId);
}

// Get Attendance Records from XML
function getAttendanceFromXML() {
    const xmlDoc = parseXMLData();
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

// Get Leave Requests from XML
function getLeaveRequestsFromXML() {
    const xmlDoc = parseXMLData();
    if (!xmlDoc) return [];
    
    const leaveRequests = [];
    const leaveNodes = xmlDoc.querySelectorAll('leave');
    
    leaveNodes.forEach(leaveNode => {
        const leave = {
            id: leaveNode.getAttribute('id'),
            employee_id: leaveNode.getAttribute('employee_id'),
            type: leaveNode.getAttribute('type'),
            status: leaveNode.getAttribute('status'),
            start: leaveNode.querySelector('start')?.textContent || '',
            end: leaveNode.querySelector('end')?.textContent || '',
            days: leaveNode.querySelector('days')?.textContent || '0'
        };
        leaveRequests.push(leave);
    });
    
    return leaveRequests;
}

// Get Leave Requests by Employee ID from XML
function getLeaveRequestsByEmployeeIdFromXML(employeeId) {
    const leaveRequests = getLeaveRequestsFromXML();
    return leaveRequests.filter(l => l.employee_id === employeeId);
}

// Get Payroll Periods from XML
function getPayrollPeriodsFromXML() {
    const xmlDoc = parseXMLData();
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

// Get Payroll Records from XML
function getPayrollRecordsFromXML() {
    const xmlDoc = parseXMLData();
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

// Get System Settings from XML
function getSystemSettingsFromXML() {
    const xmlDoc = parseXMLData();
    if (!xmlDoc) return {};
    
    const settings = {};
    const settingNodes = xmlDoc.querySelectorAll('setting');
    
    settingNodes.forEach(settingNode => {
        const key = settingNode.getAttribute('key');
        const value = settingNode.textContent;
        if (key) {
            settings[key] = value;
        }
    });
    
    return settings;
}

// Get System Setting by Key from XML
function getSystemSettingByKeyFromXML(key) {
    const settings = getSystemSettingsFromXML();
    return settings[key] || null;
}

// Example: Use XML data
console.log('XML Users:', getUsersFromXML());
console.log('XML Employees:', getEmployeesFromXML());
console.log('XML Attendance:', getAttendanceFromXML());
console.log('XML Leave Requests:', getLeaveRequestsFromXML());
console.log('XML Payroll Periods:', getPayrollPeriodsFromXML());
console.log('XML Payroll Records:', getPayrollRecordsFromXML());
console.log('XML System Settings:', getSystemSettingsFromXML());

// Add dark theme styles for modals
document.addEventListener('DOMContentLoaded', function() {
    // Ensure modals have dark theme
    const style = document.createElement('style');
    style.textContent = `
        .modal-content {
            background-color: #1e293b !important;
            color: #e5e7eb !important;
        }
        .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .form-check-input {
            background-color: #0f172a;
            border-color: #334155;
        }
    `;
    document.head.appendChild(style);
    
    // XML data is now available internally
    console.log('Payroll XML Data loaded internally');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
