<?php
require_once 'config.php';
checkLogin();

$conn = getDBConnection();

/* ===============================
   ENSURE USER SESSION DATA
================================ */
if (!isset($_SESSION['full_name'])) {
    $stmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
}

/* ===============================
   ALERTS & STATS
================================ */
checkAlerts($conn);

$total_products = $conn->query(
    "SELECT COUNT(*) AS count FROM products WHERE is_active = 1"
)->fetch_assoc()['count'] ?? 0;

$low_stock_count = $conn->query(
    "SELECT COUNT(*) AS count 
     FROM products 
     WHERE quantity_in_stock <= reorder_level AND is_active = 1"
)->fetch_assoc()['count'] ?? 0;

$expired_count = $conn->query(
    "SELECT COUNT(*) AS count 
     FROM products 
     WHERE expiry_date IS NOT NULL 
     AND expiry_date <= CURDATE() 
     AND is_active = 1"
)->fetch_assoc()['count'] ?? 0;

$total_value = $conn->query(
    "SELECT SUM(unit_price * quantity_in_stock) AS total 
     FROM products WHERE is_active = 1"
)->fetch_assoc()['total'] ?? 0;

/* ===============================
   DATA TABLES
================================ */
$recent_transactions = $conn->query(
    "SELECT st.transaction_type, st.quantity, st.transaction_date,
            p.product_name, u.full_name
     FROM stock_transactions st
     JOIN products p ON st.product_id = p.product_id
     JOIN users u ON st.user_id = u.user_id
     ORDER BY st.transaction_date DESC
     LIMIT 10"
);

$low_stock_items = $conn->query("SELECT * FROM low_stock_items LIMIT 5");
$expiring_items  = $conn->query("SELECT * FROM expiring_soon_items LIMIT 5");
$unread_alerts   = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
<div class="navbar-content">
    <div class="navbar-brand">
        <i class="fas fa-hospital"></i>
        <h1><?= SITE_NAME ?></h1>
    </div>

    <ul class="navbar-menu">
        <li><a href="dashboard.php" class="active">Dashboard</a></li>
        <li><a href="inventory.php">Inventory</a></li>
        <li><a href="transactions.php">Transactions</a></li>

        <?php if (in_array($_SESSION['role'], ['admin','pharmacist'])): ?>
            <li><a href="suppliers.php">Suppliers</a></li>
        <?php endif; ?>

        <li><a href="reports.php">Reports</a></li>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li><a href="users.php">Users</a></li>
        <?php endif; ?>
    </ul>

    <div class="navbar-right">
        <div class="notification-badge" onclick="location.href='alerts.php'">
            <i class="fas fa-bell"></i>
            <?php if ($unread_alerts > 0): ?>
                <span class="badge"><?= $unread_alerts ?></span>
            <?php endif; ?>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper($_SESSION['full_name'][0]) ?>
            </div>
            <div>
                <div><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <small><?= ucfirst($_SESSION['role']) ?></small>
            </div>
        </div>

        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</div>
</nav>

<div class="container">
<h2>Dashboard</h2>

<div class="stats-grid">
    <div class="stat-card"><h3><?= $total_products ?></h3><p>Products</p></div>
    <div class="stat-card"><h3><?= $low_stock_count ?></h3><p>Low Stock</p></div>
    <div class="stat-card"><h3><?= $expired_count ?></h3><p>Expired</p></div>
    <div class="stat-card"><h3><?= formatCurrency($total_value) ?></h3><p>Value</p></div>
</div>

<h3>Recent Transactions</h3>
<table>
<tr><th>Product</th><th>Type</th><th>Qty</th><th>User</th><th>Date</th></tr>
<?php while ($row = $recent_transactions->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['product_name']) ?></td>
    <td><?= $row['transaction_type'] === 'in' ? 'IN' : 'OUT' ?></td>
    <td><?= $row['quantity'] ?></td>
    <td><?= htmlspecialchars($row['full_name']) ?></td>
    <td><?= formatDate($row['transaction_date']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<script src="js/main.js"></script>
</body>
</html>

<?php $conn->close(); ?>
