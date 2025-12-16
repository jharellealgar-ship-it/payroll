<?php
// Start output buffering to prevent header issues
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

Auth::checkSessionTimeout();
$currentUser = Auth::getCurrentUser();
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
    <?php if (isset($useDarkTheme) && $useDarkTheme): ?>
    <!-- Dark Theme CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/dark.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <?php if (Auth::isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
                <i class="bi bi-calculator"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <?php if (Auth::hasRole('hr') || Auth::hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-people"></i> Employees
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>employees/index.php">All Employees</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>employees/add.php">Add Employee</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-clock-history"></i> Attendance
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>attendance/index.php">View Attendance</a></li>
                            <?php if (Auth::hasRole('hr') || Auth::hasRole('admin')): ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>attendance/manage.php">Manage Attendance</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>attendance/leave_requests.php">Leave Requests</a></li>
                        </ul>
                    </li>
                    <?php if (Auth::hasRole('employee') && !Auth::hasRole('hr') && !Auth::hasRole('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>payroll/my_payslips.php">
                            <i class="bi bi-file-earmark-text"></i> My Payslips
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (Auth::hasRole('hr') || Auth::hasRole('accountant') || Auth::hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-cash-coin"></i> Payroll
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>payroll/periods.php">Payroll Periods</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>payroll/compute.php">Compute Payroll</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>payroll/reports.php">Reports</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if (Auth::hasRole('admin')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings/users.php">System Users</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings/deductions.php">Deduction Types</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings/incentives.php">Incentive Types</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings/system.php">System Settings</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['full_name']); ?>
                            <span class="badge bg-<?php echo getRoleBadge($currentUser['role']); ?> ms-1">
                                <?php echo ucfirst($currentUser['role']); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php">My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $flashMessage['type'] == 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container-fluid py-4">

