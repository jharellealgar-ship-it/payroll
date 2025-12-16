<?php
/**
 * Quick Fix Script - Update Admin Password
 * Run this script once to fix the admin password in your database
 */

require_once __DIR__ . '/config/config.php';

$database = new Database();
$conn = $database->getConnection();

// Correct password hash for "admin123"
$correctHash = '$2y$10$D82LoPDigUHqvxCIhcLrj.qZ99Jecge8Bx0L2hk.KeKLh2LevvqEm';

try {
    // Update admin password
    $query = "UPDATE users SET password_hash = :hash WHERE username = 'admin'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':hash', $correctHash);
    
    if ($stmt->execute()) {
        echo "✅ Admin password has been updated successfully!\n";
        echo "You can now login with:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "❌ Failed to update password.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

