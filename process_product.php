<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = sanitize($conn, $_POST['product_name']);
    $product_code = sanitize($conn, $_POST['product_code']);
    $category_id = intval($_POST['category_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $description = sanitize($conn, $_POST['description']);
    $unit_price = floatval($_POST['unit_price']);
    $quantity_in_stock = intval($_POST['quantity_in_stock']);
    $reorder_level = intval($_POST['reorder_level']);
    $unit_of_measure = sanitize($conn, $_POST['unit_of_measure']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitize($conn, $_POST['expiry_date']) : null;
    $batch_number = sanitize($conn, $_POST['batch_number']);
    $storage_location = sanitize($conn, $_POST['storage_location']);
    $requires_prescription = isset($_POST['requires_prescription']) ? 1 : 0;
    
    if ($product_id > 0) {
        // Update existing product
        $sql = "UPDATE products SET 
                product_name = ?, product_code = ?, category_id = ?, supplier_id = ?,
                description = ?, unit_price = ?, quantity_in_stock = ?, reorder_level = ?,
                unit_of_measure = ?, expiry_date = ?, batch_number = ?, storage_location = ?,
                requires_prescription = ?
                WHERE product_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiisdiissssi", 
            $product_name, $product_code, $category_id, $supplier_id, $description,
            $unit_price, $quantity_in_stock, $reorder_level, $unit_of_measure,
            $expiry_date, $batch_number, $storage_location, $requires_prescription, $product_id
        );
        
        if ($stmt->execute()) {
            logAudit($conn, $_SESSION['user_id'], 'Updated product', 'products', $product_id);
            $response['success'] = true;
            $response['message'] = 'Product updated successfully!';
        } else {
            $response['message'] = 'Error updating product: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        // Insert new product
        $sql = "INSERT INTO products (
                product_name, product_code, category_id, supplier_id, description,
                unit_price, quantity_in_stock, reorder_level, unit_of_measure,
                expiry_date, batch_number, storage_location, requires_prescription
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiisdiisisssi", 
            $product_name, $product_code, $category_id, $supplier_id, $description,
            $unit_price, $quantity_in_stock, $reorder_level, $unit_of_measure,
            $expiry_date, $batch_number, $storage_location, $requires_prescription
        );
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            logAudit($conn, $_SESSION['user_id'], 'Added new product', 'products', $new_id);
            $response['success'] = true;
            $response['message'] = 'Product added successfully!';
        } else {
            $response['message'] = 'Error adding product: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?>