<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supplier_id']) && isset($_POST['status'])) {
    $supplier_id = intval($_POST['supplier_id']);
    $status = intval($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE suppliers SET is_active = ? WHERE supplier_id = ?");
    $stmt->bind_param("ii", $status, $supplier_id);
    
    if ($stmt->execute()) {
        $action = $status ? 'activated' : 'deactivated';
        logAudit($conn, $_SESSION['user_id'], "Supplier $action", 'suppliers', $supplier_id);
        $response['success'] = true;
        $response['message'] = "Supplier $action successfully!";
    } else {
        $response['message'] = 'Error updating supplier status: ' . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>