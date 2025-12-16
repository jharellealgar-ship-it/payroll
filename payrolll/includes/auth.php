<?php
/**
 * Authentication Helper Functions
 * Professional Payroll Management System
 */

require_once __DIR__ . '/../config/config.php';

class Auth {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole($requiredRole) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['role'] ?? '';
        
        // Role hierarchy: admin > hr/accountant > employee
        $roleHierarchy = [
            'admin' => 4,
            'hr' => 3,
            'accountant' => 3,
            'employee' => 1
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }

    /**
     * Require login - redirect if not logged in
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }

    /**
     * Require role - redirect if user doesn't have required role
     */
    public static function requireRole($requiredRole) {
        self::requireLogin();
        if (!self::hasRole($requiredRole)) {
            header('Location: ' . BASE_URL . 'index.php?error=access_denied');
            exit();
        }
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        $query = "SELECT id, username, email, password_hash, role, first_name, last_name, is_active 
                  FROM users 
                  WHERE username = :username OR email = :email 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Your account has been deactivated.'];
            }
            
            if (password_verify($password, $user['password_hash'])) {
                // Update last login
                $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['login_time'] = time();
                
                return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
        } else {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
    }

    /**
     * Logout user
     */
    public static function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }

    /**
     * Get current user info
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name']
        ];
    }

    /**
     * Check session timeout
     */
    public static function checkSessionTimeout() {
        if (self::isLoggedIn()) {
            if (isset($_SESSION['login_time'])) {
                if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                    self::logout();
                } else {
                    $_SESSION['login_time'] = time(); // Refresh session
                }
            }
        }
    }
}

