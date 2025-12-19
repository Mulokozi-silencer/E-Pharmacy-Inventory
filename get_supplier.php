<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false];

if (isset($_GET['id'])) {
    $supplier_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $response['success'] = true;
        $response['supplier'] = $result->fetch_assoc();
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>