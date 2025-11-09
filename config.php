<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_event_system');

// Enhanced session security
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id();
    $_SESSION['initiated'] = true;
}

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Check user role
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    return in_array($user['role'], (array)$roles);
}

// Check if user can modify registration
function canModifyRegistration($registrationUserId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    // Admin, Staff, or the user themselves can modify
    return in_array($user['role'], ['Admin', 'Staff']) || $user['id'] == $registrationUserId;
}

// Enhanced security functions
function validateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        return false;
    }
    return true;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Rate limiting function
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 900) { // 15 minutes
    $conn = getDBConnection();
    $time_limit = time() - $time_window;
    
    // Clean old attempts
    $conn->query("DELETE FROM login_attempts WHERE attempt_time < $time_limit");
    
    // Count recent attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE identifier = ? AND attempt_time > ?");
    $stmt->bind_param("si", $identifier, $time_limit);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['attempts'] >= $max_attempts) {
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // Log this attempt
    $stmt = $conn->prepare("INSERT INTO login_attempts (identifier, ip_address, attempt_time) VALUES (?, ?, ?)");
    $current_time = time();
    $stmt->bind_param("ssi", $identifier, $_SERVER['REMOTE_ADDR'], $current_time);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    return true;
}

// Auto-logout after 30 minutes of inactivity
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update login time on each request
if (isLoggedIn()) {
    $_SESSION['login_time'] = time();
}

// Create login attempts table (run once)
function createLoginAttemptsTable() {
    $conn = getDBConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `identifier` varchar(255) NOT NULL,
        `ip_address` varchar(45) NOT NULL,
        `attempt_time` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `identifier` (`identifier`),
        KEY `attempt_time` (`attempt_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->query($sql);
    $conn->close();
}

// Uncomment to create table (run once)
// createLoginAttemptsTable();
?>