<?php
require_once 'config.php';
checkLogin();

$conn = getDBConnection();

// Mark alert as read if requested
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $alert_id = intval($_GET['id']);
    $conn->query("UPDATE alerts SET is_read = 1 WHERE alert_id = $alert_id");
    header("Location: alerts.php");
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE alerts SET is_read = 1 WHERE is_read = 0");
    header("Location: alerts.php");
    exit;
}

// Get alerts
$alerts = $conn->query("SELECT a.*, p.product_name, p.product_code 
                       FROM alerts a 
                       JOIN products p ON a.product_id = p.product_id 
                       ORDER BY a.is_read ASC, a.created_at DESC");

$unread_alerts = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts & Notifications - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <i class="fas fa-hospital"></i>
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'pharmacist'): ?>
                <li><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <?php endif; ?>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="navbar-right">
                <div class="notification-badge" onclick="window.location.href='alerts.php'">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_alerts > 0): ?>
                    <span class="badge"><?php echo $unread_alerts; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-size: 14px; font-weight: 500;"><?php echo $_SESSION['full_name']; ?></div>
                        <div style="font-size: 12px; color: #9ca3af;"><?php echo ucfirst($_SESSION['role']); ?></div>
                    </div>
                </div>
                
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h2>Alerts & Notifications</h2>
            <div class="breadcrumb">Home / Alerts</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>System Alerts (<?php echo $unread_alerts; ?> unread)</h3>
                <?php if ($unread_alerts > 0): ?>
                <a href="alerts.php?mark_all_read=1" class="btn btn-primary btn-sm">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($alerts->num_rows == 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No alerts at this time.
                    </div>
                <?php else: ?>
                    <?php while ($row = $alerts->fetch_assoc()): ?>
                        <div class="alert <?php 
                            if ($row['alert_type'] == 'low_stock') echo 'alert-warning';
                            elseif ($row['alert_type'] == 'expiry_soon') echo 'alert-warning';
                            elseif ($row['alert_type'] == 'expired') echo 'alert-danger';
                        ?>" style="<?php echo $row['is_read'] ? 'opacity: 0.6;' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <strong>
                                        <?php 
                                        if ($row['alert_type'] == 'low_stock') echo '<i class="fas fa-exclamation-triangle"></i> Low Stock Alert';
                                        elseif ($row['alert_type'] == 'expiry_soon') echo '<i class="fas fa-clock"></i> Expiry Warning';
                                        elseif ($row['alert_type'] == 'expired') echo '<i class="fas fa-ban"></i> Expired Item';
                                        ?>
                                    </strong>
                                    <p style="margin: 10px 0 5px 0;"><?php echo htmlspecialchars($row['alert_message']); ?></p>
                                    <small style="color: #6b7280;">
                                        Product: <?php echo htmlspecialchars($row['product_name']); ?> (<?php echo $row['product_code']; ?>) • 
                                        <?php echo formatDate($row['created_at']); ?>
                                    </small>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <a href="inventory.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Inventory
                                    </a>
                                    <?php if (!$row['is_read']): ?>
                                    <a href="alerts.php?mark_read=1&id=<?php echo $row['alert_id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Mark Read
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>