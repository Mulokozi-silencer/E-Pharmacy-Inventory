<?php
require_once 'config.php';
checkLogin();

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false];

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $response['success'] = true;
        $response['product'] = $result->fetch_assoc();
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>d