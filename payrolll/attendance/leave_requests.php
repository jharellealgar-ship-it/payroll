<?php
$pageTitle = 'Leave Requests';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();

$database = new Database();
$conn = $database->getConnection();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $requestId = intval($_POST['request_id'] ?? 0);
    $action = sanitize($_POST['action'] ?? '');
    
    if ($requestId > 0 && in_array($action, ['approve', 'reject'])) {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        $rejectionReason = $action == 'reject' ? sanitize($_POST['rejection_reason'] ?? '') : null;
        
        $query = "UPDATE leave_requests SET status = :status, approved_by = :approved_by, 
                  approved_at = NOW(), rejection_reason = :rejection_reason 
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindValue(':approved_by', $_SESSION['user_id']);
        $stmt->bindValue(':rejection_reason', $rejectionReason);
        $stmt->bindParam(':id', $requestId);
        $stmt->execute();
        
        redirect('attendance/leave_requests.php', 'Leave request ' . $action . 'd successfully!', 'success');
    }
}

// Build query based on role
if (Auth::hasRole('hr') || Auth::hasRole('admin')) {
    $query = "SELECT lr.*, e.employee_id, e.first_name, e.last_name, u.first_name as approver_first, u.last_name as approver_last
              FROM leave_requests lr
              INNER JOIN employees e ON lr.employee_id = e.id
              LEFT JOIN users u ON lr.approved_by = u.id
              ORDER BY lr.created_at DESC";
} else {
    // Employees see only their own requests
    $query = "SELECT lr.*, e.employee_id, e.first_name, e.last_name, u.first_name as approver_first, u.last_name as approver_last
              FROM leave_requests lr
              INNER JOIN employees e ON lr.employee_id = e.id
              LEFT JOIN users u ON lr.approved_by = u.id
              WHERE e.user_id = :user_id
              ORDER BY lr.created_at DESC";
}

$stmt = $conn->prepare($query);
if (!Auth::hasRole('hr') && !Auth::hasRole('admin')) {
    $stmt->bindValue(':user_id', $_SESSION['user_id']);
}
$stmt->execute();
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Leave Requests</h1>
                    <p class="text-muted">Manage employee leave requests</p>
                </div>
                <?php if (!Auth::hasRole('hr') && !Auth::hasRole('admin')): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestLeaveModal">
                    <i class="bi bi-plus-circle"></i> Request Leave
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Leave Requests (<?php echo count($requests); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($requests) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($req['employee_id']); ?></strong><br>
                                <small><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></small>
                            </td>
                            <td><?php echo ucfirst(str_replace('-', ' ', $req['leave_type'])); ?></td>
                            <td><?php echo formatDate($req['start_date']); ?></td>
                            <td><?php echo formatDate($req['end_date']); ?></td>
                            <td><?php echo number_format($req['days_requested'], 1); ?></td>
                            <td><?php echo htmlspecialchars(substr($req['reason'], 0, 50)) . (strlen($req['reason']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadge($req['status']); ?>">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['approver_first']): ?>
                                <?php echo htmlspecialchars($req['approver_first'] . ' ' . $req['approver_last']); ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($req['status'] == 'pending') && (Auth::hasRole('hr') || Auth::hasRole('admin'))): ?>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-success" onclick="approveRequest(<?php echo $req['id']; ?>)">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="rejectRequest(<?php echo $req['id']; ?>)">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">No leave requests found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Request Leave Modal -->
<?php if (!Auth::hasRole('hr') && !Auth::hasRole('admin')): ?>
<div class="modal fade" id="requestLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="request_leave.php">
                <div class="modal-header">
                    <h5 class="modal-title">Request Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="leave_type" required>
                            <option value="vacation">Vacation</option>
                            <option value="sick">Sick Leave</option>
                            <option value="emergency">Emergency</option>
                            <option value="maternity">Maternity</option>
                            <option value="paternity">Paternity</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function approveRequest(id) {
    if (confirm('Approve this leave request?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="request_id" value="' + id + '"><input type="hidden" name="action" value="approve">';
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequest(id) {
    const reason = prompt('Enter rejection reason:');
    if (reason !== null && reason.trim() !== '') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="request_id" value="' + id + '"><input type="hidden" name="action" value="reject"><input type="hidden" name="rejection_reason" value="' + reason + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<script>
// ================= INTERNAL LEAVE REQUESTS XML DATA =================
const leaveRequestsXMLData = `<leave_requests>
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
</leave_requests>`;

// Parse Leave Requests XML Data Function
function parseLeaveRequestsXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(leaveRequestsXMLData, 'text/xml');
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('Leave Requests XML Parse Error:', parseError.textContent);
            return null;
        }
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing Leave Requests XML:', error);
        return null;
    }
}

// Get Leave Requests from XML
function getLeaveRequestsFromXML() {
    const xmlDoc = parseLeaveRequestsXMLData();
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

// Get Leave Requests by Status from XML
function getLeaveRequestsByStatusFromXML(status) {
    const leaveRequests = getLeaveRequestsFromXML();
    return leaveRequests.filter(l => l.status === status);
}

console.log('XML Leave Requests:', getLeaveRequestsFromXML());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>