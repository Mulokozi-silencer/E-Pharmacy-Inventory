<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // Soft delete - set is_active to 0
    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        logAudit($conn, $_SESSION['user_id'], 'Deleted product', 'products', $product_id);
        $response['success'] = true;
        $response['message'] = 'Product deleted successfully!';
    } else {
        $response['message'] = 'Error deleting product: ' . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>