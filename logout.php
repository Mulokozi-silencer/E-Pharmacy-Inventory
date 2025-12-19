<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $conn = getDBConnection();
    logAudit($conn, $_SESSION['user_id'], 'User logged out');
    $conn->close();
}

session_unset();
session_destroy();
header("Location: login.php?logout=1");
exit();
?>