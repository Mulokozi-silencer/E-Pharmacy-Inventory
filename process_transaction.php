<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_type = sanitize($conn, $_POST['transaction_type']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $reference_number = sanitize($conn, $_POST['reference_number']);
    $notes = sanitize($conn, $_POST['notes']);
    $patient_name = isset($_POST['patient_name']) ? sanitize($conn, $_POST['patient_name']) : null;
    $doctor_name = isset($_POST['doctor_name']) ? sanitize($conn, $_POST['doctor_name']) : null;
    
    // Get current stock
    $stock_result = $conn->query("SELECT quantity_in_stock, product_name FROM products WHERE product_id = $product_id");
    
    if ($stock_result->num_rows == 0) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }
    
    $stock_data = $stock_result->fetch_assoc();
    $current_stock = $stock_data['quantity_in_stock'];
    $product_name = $stock_data['product_name'];
    
    // Validate stock for stock out
    if ($transaction_type == 'out' && $current_stock < $quantity) {
        $response['message'] = "Insufficient stock. Available: $current_stock";
        echo json_encode($response);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update stock
        if ($transaction_type == 'in') {
            $new_stock = $current_stock + $quantity;
        } else {
            $new_stock = $current_stock - $quantity;
        }
        
        $update_sql = "UPDATE products SET quantity_in_stock = ? WHERE product_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_stock, $product_id);
        $update_stmt->execute();
        
        // Insert transaction record
        $insert_sql = "INSERT INTO stock_transactions 
                      (product_id, transaction_type, quantity, user_id, reference_number, notes, patient_name, doctor_name) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $user_id = $_SESSION['user_id'];
        $insert_stmt->bind_param("isiissss", 
            $product_id, $transaction_type, $quantity, $user_id, 
            $reference_number, $notes, $patient_name, $doctor_name
        );
        $insert_stmt->execute();
        
        // Log audit
        $action = $transaction_type == 'in' ? 'Stock in' : 'Stock out';
        logAudit($conn, $user_id, "$action - $product_name - Quantity: $quantity", 'stock_transactions', $conn->insert_id);
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = ucfirst($transaction_type == 'in' ? 'Stock added' : 'Stock removed') . ' successfully!';
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error processing transaction: ' . $e->getMessage();
    }
}

$conn->close();
echo json_encode($response);
?>