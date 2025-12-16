<?php
$pageTitle = 'Add Employee';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('hr');

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = sanitize($_POST['employee_id'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $position = sanitize($_POST['position'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $baseSalary = floatval($_POST['base_salary'] ?? 0);
    $hireDate = sanitize($_POST['hire_date'] ?? '');
    
    // Handle photo upload
    $photoFilename = null;
    if (!empty($_POST['photo_data'])) {
        // Handle base64 image data from camera
        $photoData = $_POST['photo_data'];
        if (strpos($photoData, 'data:image') === 0) {
            // Extract image data
            list($type, $data) = explode(';', $photoData);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);
            
            // Generate unique filename
            $extension = 'jpg'; // Default to jpg
            if (strpos($type, 'png') !== false) {
                $extension = 'png';
            }
            $photoFilename = $employeeId . '_' . time() . '.' . $extension;
            $photoPath = UPLOAD_DIR . 'employees/' . $photoFilename;
            
            // Save file
            if (!is_dir(UPLOAD_DIR . 'employees/')) {
                mkdir(UPLOAD_DIR . 'employees/', 0755, true);
            }
            file_put_contents($photoPath, $data);
        }
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Handle regular file upload
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $fileType = $_FILES['photo']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoFilename = $employeeId . '_' . time() . '.' . $extension;
            $photoPath = UPLOAD_DIR . 'employees/' . $photoFilename;
            
            if (!is_dir(UPLOAD_DIR . 'employees/')) {
                mkdir(UPLOAD_DIR . 'employees/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                // File uploaded successfully
            } else {
                $error = 'Failed to upload photo.';
            }
        } else {
            $error = 'Invalid file type. Please upload JPEG, PNG, or GIF images only.';
        }
    }
    
    // Validation
    if (empty($employeeId) || empty($firstName) || empty($lastName) || empty($email) || empty($position) || empty($department) || empty($hireDate)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if employee ID or email already exists
        $checkStmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = :employee_id OR email = :email");
        $checkStmt->bindParam(':employee_id', $employeeId);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $error = 'Employee ID or Email already exists.';
        } else {
            // Check if user account would conflict (username or email)
            $userCheck = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $userCheck->bindParam(':username', $employeeId);
            $userCheck->bindParam(':email', $email);
            $userCheck->execute();

            if ($userCheck->rowCount() > 0) {
                $error = 'A user account already exists with this Employee ID or Email.';
            } else {
                try {
                    $conn->beginTransaction();

                    // Insert employee
                    $query = "INSERT INTO employees (employee_id, first_name, last_name, middle_name, email, phone, address, 
                              date_of_birth, gender, marital_status, position, department, employment_type, employment_status, 
                              hire_date, base_salary, hourly_rate, bank_name, bank_account, tax_id, sss_number, philhealth_number, pagibig_number, photo) 
                              VALUES (:employee_id, :first_name, :last_name, :middle_name, :email, :phone, :address, 
                              :date_of_birth, :gender, :marital_status, :position, :department, :employment_type, :employment_status, 
                              :hire_date, :base_salary, :hourly_rate, :bank_name, :bank_account, :tax_id, :sss_number, :philhealth_number, :pagibig_number, :photo)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':employee_id', $employeeId);
                    $stmt->bindParam(':first_name', $firstName);
                    $stmt->bindParam(':last_name', $lastName);
                    $stmt->bindValue(':middle_name', sanitize($_POST['middle_name'] ?? ''));
                    $stmt->bindParam(':email', $email);
                    $stmt->bindValue(':phone', sanitize($_POST['phone'] ?? ''));
                    $stmt->bindValue(':address', sanitize($_POST['address'] ?? ''));
                    $stmt->bindValue(':date_of_birth', !empty($_POST['date_of_birth']) ? sanitize($_POST['date_of_birth']) : null);
                    $stmt->bindValue(':gender', sanitize($_POST['gender'] ?? ''));
                    $stmt->bindValue(':marital_status', sanitize($_POST['marital_status'] ?? ''));
                    $stmt->bindParam(':position', $position);
                    $stmt->bindParam(':department', $department);
                    $stmt->bindValue(':employment_type', sanitize($_POST['employment_type'] ?? 'full-time'));
                    $stmt->bindValue(':employment_status', 'active');
                    $stmt->bindParam(':hire_date', $hireDate);
                    $stmt->bindParam(':base_salary', $baseSalary);
                    $stmt->bindValue(':hourly_rate', !empty($_POST['hourly_rate']) ? floatval($_POST['hourly_rate']) : null);
                    $stmt->bindValue(':bank_name', sanitize($_POST['bank_name'] ?? ''));
                    $stmt->bindValue(':bank_account', sanitize($_POST['bank_account'] ?? ''));
                    $stmt->bindValue(':tax_id', sanitize($_POST['tax_id'] ?? ''));
                    $stmt->bindValue(':sss_number', sanitize($_POST['sss_number'] ?? ''));
                    $stmt->bindValue(':philhealth_number', sanitize($_POST['philhealth_number'] ?? ''));
                    $stmt->bindValue(':pagibig_number', sanitize($_POST['pagibig_number'] ?? ''));
                    $stmt->bindValue(':photo', $photoFilename);
                    
                    if ($stmt->execute()) {
                        $newEmployeeId = $conn->lastInsertId();

                        // Create linked user account with employee ID as username and password
                        $passwordHash = password_hash($employeeId, PASSWORD_DEFAULT);
                        $createUser = $conn->prepare("INSERT INTO users (username, email, password_hash, role, first_name, last_name) 
                                                      VALUES (:username, :email, :password_hash, :role, :first_name, :last_name)");
                        $createUser->bindParam(':username', $employeeId);
                        $createUser->bindParam(':email', $email);
                        $createUser->bindParam(':password_hash', $passwordHash);
                        $createUser->bindValue(':role', 'employee');
                        $createUser->bindParam(':first_name', $firstName);
                        $createUser->bindParam(':last_name', $lastName);

                        if ($createUser->execute()) {
                            $newUserId = $conn->lastInsertId();

                            // Link user to employee
                            $linkUser = $conn->prepare("UPDATE employees SET user_id = :user_id WHERE id = :emp_id");
                            $linkUser->bindParam(':user_id', $newUserId);
                            $linkUser->bindParam(':emp_id', $newEmployeeId);
                            $linkUser->execute();

                            $conn->commit();

                            logActivity('Employee Created', 'employees', $newEmployeeId, null, ['employee_id' => $employeeId]);
                            logActivity('User Account Created for Employee', 'users', $newUserId, null, ['employee_id' => $employeeId]);
                            redirect('employees/index.php', 'Employee added successfully! Login created with Employee ID as password.', 'success');
                        } else {
                            $conn->rollBack();
                            $error = 'Failed to create user account for employee.';
                        }
                    } else {
                        $conn->rollBack();
                        $error = 'Failed to add employee. Please try again.';
                    }
                } catch (Exception $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $error = 'Failed to add employee. Please try again.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Add New Employee</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>employees/index.php">Employees</a></li>
                    <li class="breadcrumb-item active">Add Employee</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="employee_id" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Employee Photo</label>
                                <div class="border rounded p-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <!-- Camera Capture -->
                                            <div class="mb-3">
                                                <label class="form-label small">Capture from Camera</label>
                                                <div id="camera-container" class="mb-2">
                                                    <video id="video" width="100%" height="240" autoplay style="display: none; background: #000; border-radius: 4px;"></video>
                                                    <canvas id="canvas" style="display: none;"></canvas>
                                                    <div id="camera-preview" style="width: 100%; height: 240px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer;" onclick="document.getElementById('captureBtn').click()">
                                                        <div class="text-center text-muted">
                                                            <i class="bi bi-camera fs-1"></i>
                                                            <p class="mt-2 mb-0">Click to start camera</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-group w-100 mb-2" role="group">
                                                    <button type="button" class="btn btn-primary" id="startCamera">
                                                        <i class="bi bi-camera-video"></i> Start Camera
                                                    </button>
                                                    <button type="button" class="btn btn-success" id="captureBtn" style="display: none;">
                                                        <i class="bi bi-camera-fill"></i> Capture Photo
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="stopCamera" style="display: none;">
                                                        <i class="bi bi-stop-circle"></i> Stop Camera
                                                    </button>
                                                    <button type="button" class="btn btn-danger" id="clearPhoto" style="display: none;">
                                                        <i class="bi bi-x-circle"></i> Clear
                                                    </button>
                                                </div>
                                                <div id="captured-preview" style="display: none;">
                                                    <img id="captured-img" src="" alt="Captured Photo" style="max-width: 100%; height: auto; border-radius: 4px;">
                                                    <input type="hidden" name="photo_data" id="photo_data">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- File Upload -->
                                            <div>
                                                <label class="form-label small">Or Upload from File</label>
                                                <input type="file" class="form-control" name="photo" id="photo_file" accept="image/jpeg,image/png,image/jpg,image/gif">
                                                <small class="text-muted">Accept JPEG, PNG, or GIF images</small>
                                                <div id="file-preview" class="mt-2" style="display: none;">
                                                    <img id="file-preview-img" src="" alt="Preview" style="max-width: 100%; max-height: 240px; border-radius: 4px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marital Status</label>
                                <select class="form-select" name="marital_status">
                                    <option value="">Select Status</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="divorced">Divorced</option>
                                    <option value="widowed">Widowed</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Employment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="position" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="department" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employment Type</label>
                                <select class="form-select" name="employment_type">
                                    <option value="full-time">Full-time</option>
                                    <option value="part-time">Part-time</option>
                                    <option value="contract">Contract</option>
                                    <option value="intern">Intern</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="hire_date" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Base Salary <span class="text-danger">*</span></label>
                                <input type="number" class="form-control currency-input" name="base_salary" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hourly Rate</label>
                                <input type="number" class="form-control currency-input" name="hourly_rate" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-save"></i> Save Employee
                        </button>
                        <a href="<?php echo BASE_URL; ?>employees/index.php" class="btn btn-secondary w-100">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let stream = null;
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const startCameraBtn = document.getElementById('startCamera');
const captureBtn = document.getElementById('captureBtn');
const stopCameraBtn = document.getElementById('stopCamera');
const clearPhotoBtn = document.getElementById('clearPhoto');
const cameraPreview = document.getElementById('camera-preview');
const capturedPreview = document.getElementById('captured-preview');
const capturedImg = document.getElementById('captured-img');
const photoData = document.getElementById('photo_data');
const photoFile = document.getElementById('photo_file');
const filePreview = document.getElementById('file-preview');
const filePreviewImg = document.getElementById('file-preview-img');

// Start camera
startCameraBtn.addEventListener('click', async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        });
        video.srcObject = stream;
        video.style.display = 'block';
        cameraPreview.style.display = 'none';
        startCameraBtn.style.display = 'none';
        captureBtn.style.display = 'inline-block';
        stopCameraBtn.style.display = 'inline-block';
    } catch (err) {
        alert('Error accessing camera: ' + err.message);
        console.error('Camera error:', err);
    }
});

// Stop camera
stopCameraBtn.addEventListener('click', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    video.style.display = 'none';
    cameraPreview.style.display = 'flex';
    startCameraBtn.style.display = 'inline-block';
    captureBtn.style.display = 'none';
    stopCameraBtn.style.display = 'none';
});

// Capture photo
captureBtn.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    
    const dataURL = canvas.toDataURL('image/jpeg', 0.8);
    capturedImg.src = dataURL;
    photoData.value = dataURL;
    capturedPreview.style.display = 'block';
    clearPhotoBtn.style.display = 'inline-block';
    
    // Stop camera after capture
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    video.style.display = 'none';
    cameraPreview.style.display = 'flex';
    startCameraBtn.style.display = 'inline-block';
    captureBtn.style.display = 'none';
    stopCameraBtn.style.display = 'none';
});

// Clear captured photo
clearPhotoBtn.addEventListener('click', () => {
    capturedPreview.style.display = 'none';
    photoData.value = '';
    capturedImg.src = '';
    clearPhotoBtn.style.display = 'none';
});

// File upload preview
photoFile.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            filePreviewImg.src = event.target.result;
            filePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        // Clear camera capture if file is selected
        photoData.value = '';
        capturedPreview.style.display = 'none';
    }
});

// Stop camera when form is submitted
document.querySelector('form').addEventListener('submit', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

