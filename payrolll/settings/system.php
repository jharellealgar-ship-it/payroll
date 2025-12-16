<?php
$pageTitle = 'System Settings';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireRole('admin');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key != 'submit') {
            $value = sanitize($value);
            $checkStmt = $conn->prepare("SELECT id FROM system_settings WHERE setting_key = :key");
            $checkStmt->bindParam(':key', $key);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $updateStmt = $conn->prepare("UPDATE system_settings SET setting_value = :value, updated_by = :user_id WHERE setting_key = :key");
                $updateStmt->bindParam(':value', $value);
                $updateStmt->bindParam(':key', $key);
                $updateStmt->bindValue(':user_id', $_SESSION['user_id']);
                $updateStmt->execute();
            } else {
                $insertStmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (:key, :value, :user_id)");
                $insertStmt->bindParam(':key', $key);
                $insertStmt->bindParam(':value', $value);
                $insertStmt->bindValue(':user_id', $_SESSION['user_id']);
                $insertStmt->execute();
            }
        }
    }
    redirect('settings/system.php', 'Settings updated successfully!', 'success');
}

// Get all settings
$settings = [];
$settingsQuery = $conn->query("SELECT setting_key, setting_value, description FROM system_settings");
foreach ($settingsQuery->fetchAll() as $setting) {
    $settings[$setting['setting_key']] = [
        'value' => $setting['setting_value'],
        'description' => $setting['description']
    ];
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">System Settings</h1>
            <p class="text-muted">Configure system-wide settings and parameters</p>
        </div>
    </div>

    <form method="POST" action="">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Company Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']['value'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Address</label>
                            <textarea class="form-control" name="company_address" rows="3"><?php echo htmlspecialchars($settings['company_address']['value'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Payroll Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Regular Hours Per Day</label>
                            <input type="number" class="form-control" name="regular_hours_per_day" step="0.5" min="1" max="24" value="<?php echo htmlspecialchars($settings['regular_hours_per_day']['value'] ?? '8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Overtime Rate Multiplier</label>
                            <input type="number" class="form-control" name="overtime_rate_multiplier" step="0.01" min="1" value="<?php echo htmlspecialchars($settings['overtime_rate_multiplier']['value'] ?? '1.25'); ?>">
                            <small class="text-muted">1.25 = 125% of regular rate</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tax & Deduction Rates</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Income Tax Rate</label>
                            <input type="number" class="form-control" name="tax_rate" step="0.01" min="0" max="1" value="<?php echo htmlspecialchars($settings['tax_rate']['value'] ?? '0.20'); ?>">
                            <small class="text-muted">Enter as decimal (0.20 = 20%)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SSS Rate</label>
                            <input type="number" class="form-control" name="sss_rate" step="0.01" min="0" max="1" value="<?php echo htmlspecialchars($settings['sss_rate']['value'] ?? '0.11'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">PhilHealth Rate</label>
                            <input type="number" class="form-control" name="philhealth_rate" step="0.01" min="0" max="1" value="<?php echo htmlspecialchars($settings['philhealth_rate']['value'] ?? '0.03'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pag-IBIG Rate</label>
                            <input type="number" class="form-control" name="pagibig_rate" step="0.01" min="0" max="1" value="<?php echo htmlspecialchars($settings['pagibig_rate']['value'] ?? '0.02'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Late Penalty Per Minute (PHP)</label>
                            <input type="number" class="form-control currency-input" name="late_penalty_per_minute" step="0.01" min="0" value="<?php echo htmlspecialchars($settings['late_penalty_per_minute']['value'] ?? '10'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Settings
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// ================= INTERNAL SYSTEM SETTINGS XML DATA =================
const systemSettingsXMLData = `<system_settings>
    <setting key="regular_hours_per_day">8</setting>
    <setting key="overtime_rate_multiplier">1.25</setting>
    <setting key="tax_rate">0.20</setting>
    <setting key="sss_rate">0.11</setting>
    <setting key="philhealth_rate">0.03</setting>
    <setting key="pagibig_rate">0.02</setting>
    <setting key="late_penalty_per_minute">10</setting>
</system_settings>`;

// Parse System Settings XML Data Function
function parseSystemSettingsXMLData() {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(systemSettingsXMLData, 'text/xml');
        const parseError = xmlDoc.querySelector('parsererror');
        if (parseError) {
            console.error('System Settings XML Parse Error:', parseError.textContent);
            return null;
        }
        return xmlDoc;
    } catch (error) {
        console.error('Error parsing System Settings XML:', error);
        return null;
    }
}

// Get System Settings from XML
function getSystemSettingsFromXML() {
    const xmlDoc = parseSystemSettingsXMLData();
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
console.log('XML System Settings:', getSystemSettingsFromXML());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>