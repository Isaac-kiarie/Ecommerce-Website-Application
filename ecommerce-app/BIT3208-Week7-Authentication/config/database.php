<?php
// =============================================
// DATABASE CONFIGURATION
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Update with your password
define('DB_NAME', 'auth_system');

/**
 * Get database connection
 */
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}

/**
 * Start session if not started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?error=Please login first');
        exit;
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $user;
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user role
 */
function getUserRole() {
    if (!isLoggedIn()) {
        return null;
    }
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return getUserRole() === $role;
}

/**
 * Check if user is administrator
 */
function isAdmin() {
    return hasRole('administrator');
}

/**
 * Log user activity
 */
function logActivity($user_id, $action) {
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $stmt->bind_param("isss", $user_id, $action, $ip, $agent);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>