<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medical_inventory');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Application Settings
define('SITE_NAME', 'Medical Inventory System');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Security function to prevent SQL injection
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Check user role
function checkRole($allowed_roles) {
    checkLogin();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: dashboard.php");
        exit();
    }
}

// Log audit trail
function logAudit($conn, $user_id, $action, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $action, $table_name, $record_id, $old_value, $new_value, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Format date
function formatDate($date) {
    if ($date) {
        return date('M d, Y', strtotime($date));
    }
    return 'N/A';
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Check and generate alerts
function checkAlerts($conn) {
    // Clear old alerts
    $conn->query("DELETE FROM alerts WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    
    // Low stock alerts
    $low_stock = $conn->query("SELECT product_id, product_name, quantity_in_stock, reorder_level FROM products WHERE quantity_in_stock <= reorder_level AND is_active = 1");
    while ($row = $low_stock->fetch_assoc()) {
        $check = $conn->query("SELECT alert_id FROM alerts WHERE product_id = {$row['product_id']} AND alert_type = 'low_stock' AND is_read = 0");
        if ($check->num_rows == 0) {
            $message = "Low stock alert: {$row['product_name']} has only {$row['quantity_in_stock']} units left (Reorder level: {$row['reorder_level']})";
            $conn->query("INSERT INTO alerts (product_id, alert_type, alert_message) VALUES ({$row['product_id']}, 'low_stock', '$message')");
        }
    }
    
    // Expiring soon alerts
    $expiring = $conn->query("SELECT product_id, product_name, expiry_date FROM products WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date > CURDATE() AND is_active = 1");
    while ($row = $expiring->fetch_assoc()) {
        $check = $conn->query("SELECT alert_id FROM alerts WHERE product_id = {$row['product_id']} AND alert_type = 'expiry_soon' AND is_read = 0");
        if ($check->num_rows == 0) {
            $message = "Expiry warning: {$row['product_name']} expires on " . formatDate($row['expiry_date']);
            $conn->query("INSERT INTO alerts (product_id, alert_type, alert_message) VALUES ({$row['product_id']}, 'expiry_soon', '$message')");
        }
    }
    
    // Expired items alerts
    $expired = $conn->query("SELECT product_id, product_name, expiry_date FROM products WHERE expiry_date IS NOT NULL AND expiry_date <= CURDATE() AND is_active = 1");
    while ($row = $expired->fetch_assoc()) {
        $check = $conn->query("SELECT alert_id FROM alerts WHERE product_id = {$row['product_id']} AND alert_type = 'expired' AND is_read = 0");
        if ($check->num_rows == 0) {
            $message = "EXPIRED: {$row['product_name']} expired on " . formatDate($row['expiry_date']);
            $conn->query("INSERT INTO alerts (product_id, alert_type, alert_message) VALUES ({$row['product_id']}, 'expired', '$message')");
        }
    }
}

// Get unread alerts count
function getUnreadAlertsCount($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM alerts WHERE is_read = 0");
    $row = $result->fetch_assoc();
    return $row['count'];
}
?>