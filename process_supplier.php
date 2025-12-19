<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

header('Content-Type: application/json');

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $supplier_name = sanitize($conn, $_POST['supplier_name']);
    $contact_person = sanitize($conn, $_POST['contact_person']);
    $email = sanitize($conn, $_POST['email']);
    $phone = sanitize($conn, $_POST['phone']);
    $address = sanitize($conn, $_POST['address']);
    
    if ($supplier_id > 0) {
        // Update existing supplier
        $sql = "UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE supplier_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $supplier_name, $contact_person, $email, $phone, $address, $supplier_id);
        
        if ($stmt->execute()) {
            logAudit($conn, $_SESSION['user_id'], 'Updated supplier', 'suppliers', $supplier_id);
            $response['success'] = true;
            $response['message'] = 'Supplier updated successfully!';
        } else {
            $response['message'] = 'Error updating supplier: ' . $conn->error;
        }
        
        $stmt->close();
    } else {
        // Insert new supplier
        $sql = "INSERT INTO suppliers (supplier_name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $supplier_name, $contact_person, $email, $phone, $address);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            logAudit($conn, $_SESSION['user_id'], 'Added new supplier', 'suppliers', $new_id);
            $response['success'] = true;
            $response['message'] = 'Supplier added successfully!';
        } else {
            $response['message'] = 'Error adding supplier: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?>