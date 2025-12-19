<?php
require_once 'config.php';
checkRole(['admin']);

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$user_id   = $_POST['user_id'] ?? null;
$username  = trim($_POST['username']);
$full_name = trim($_POST['full_name']);
$email     = trim($_POST['email']);
$phone     = trim($_POST['phone']);
$role      = $_POST['role'];
$password  = $_POST['password'] ?? '';

/* ===============================
   BASIC VALIDATION
================================ */
if (!$username || !$full_name || !$email || !$role) {
    die("Missing required fields");
}

/* ===============================
   CHECK DUPLICATES
================================ */
$sql = "SELECT user_id FROM users WHERE (username = ? OR email = ?)";

$params = [$username, $email];
$types  = "ss";

if ($user_id) {
    $sql .= " AND user_id != ?";
    $params[] = $user_id;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    die("Username or email already exists");
}

/* ===============================
   ADD USER
================================ */
if (empty($user_id)) {

    if (empty($password)) {
        die("Password required for new user");
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users 
        (username, full_name, email, phone, role, password, is_active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
    );

    $stmt->bind_param(
        "ssssss",
        $username,
        $full_name,
        $email,
        $phone,
        $role,
        $hashed
    );

/* ===============================
   UPDATE USER
================================ */
} else {

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "UPDATE users 
             SET username=?, full_name=?, email=?, phone=?, role=?, password=?
             WHERE user_id=?"
        );

        $stmt->bind_param(
            "ssssssi",
            $username,
            $full_name,
            $email,
            $phone,
            $role,
            $hashed,
            $user_id
        );

    } else {
        $stmt = $conn->prepare(
            "UPDATE users 
             SET username=?, full_name=?, email=?, phone=?, role=?
             WHERE user_id=?"
        );

        $stmt->bind_param(
            "sssssi",
            $username,
            $full_name,
            $email,
            $phone,
            $role,
            $user_id
        );
    }
}

/* ===============================
   EXECUTE & RESPOND
================================ */
if (!$stmt->execute()) {
    error_log("USER SAVE ERROR: " . $stmt->error);
    die("Database error");
}

header("Location: users.php?success=1");
exit();
