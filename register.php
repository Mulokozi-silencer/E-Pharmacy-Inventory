<?php
require_once 'config.php';

// Prevent logged-in users from registering
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    $full_name = sanitize($conn, $_POST['full_name']);
    $username  = sanitize($conn, $_POST['username']);
    $email     = sanitize($conn, $_POST['email']);
    $role      = sanitize($conn, $_POST['role']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    // Basic validation
    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check for existing username or email
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ? OR email = ?"
        );
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, username, email, password, role, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)"
            );
            $stmt->bind_param(
                "sssss",
                $full_name,
                $username,
                $email,
                $hashed,
                $role
            );

            if ($stmt->execute()) {
                logAudit($conn, $stmt->insert_id, 'User registered');
                $success = 'Registration successful. You can now log in.';
            } else {
                $error = 'Registration failed. Try again.';
            }
            $stmt->close();
        }
        $check->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="logo">
            <i class="fas fa-user-plus"></i>
        </div>
        <h2>Register</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin">Admin</option>
                    <option value="doctor">Doctor</option>
                    <option value="nurse">Nurse</option>
                    <option value="pharmacist">Pharmacist</option>
                </select>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button class="btn btn-primary" style="width:100%;">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <p style="text-align:center;margin-top:15px;">
            <a href="login.php">Back to Login</a>
        </p>
    </div>
</div>
</body>
</html>
