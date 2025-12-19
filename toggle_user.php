<?php
require_once 'config.php';
checkRole(['admin']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = intval($_POST['user_id']);
    $status = intval($_POST['status']);
    
    // Prevent deactivating own account
    if ($user_id == $_SESSION['user_id']) {
        $response['message'] = 'You cannot deactivate your own account';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $status, $user_id);
    
    if ($stmt->execute()) {
        $action = $status ? 'activated' : 'deactivated';
        logAudit($conn, $_SESSION['user_id'], "User $action", 'users', $user_id);
        $response['success'] = true;
        $response['message'] = "User $action successfully!";
    } else {
        $response['message'] = 'Error updating user status: ' . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>