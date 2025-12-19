<?php
require_once 'config.php';
checkRole(['admin']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false];

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT user_id, username, full_name, email, phone, role, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $response['success'] = true;
        $response['user'] = $result->fetch_assoc();
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>