<?php
require_once 'config.php';
checkLogin();

$conn = getDBConnection();

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch_assoc()['count'];
$total_value = $conn->query("SELECT SUM(unit_price * quantity_in_stock) as total FROM products WHERE is_active = 1")->fetch_assoc()['total'];
$low_stock_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity_in_stock <= reorder_level AND is_active = 1")->fetch_assoc()['count'];
$expired_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE expiry_date IS NOT NULL AND expiry_date <= CURDATE() AND is_active = 1")->fetch_assoc()['count'];

// Get transactions in date range
$stock_in_count = $conn->query("SELECT COUNT(*) as count, SUM(quantity) as total FROM stock_transactions WHERE transaction_type = 'in' AND transaction_date BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();
$stock_out_count = $conn->query("SELECT COUNT(*) as count, SUM(quantity) as total FROM stock_transactions WHERE transaction_type = 'out' AND transaction_date BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();

// Top products by value
$top_products = $conn->query("SELECT p.product_name, p.product_code, p.quantity_in_stock, p.unit_price, 
                              (p.quantity_in_stock * p.unit_price) as total_value,
                              c.category_name
                              FROM products p
                              LEFT JOIN categories c ON p.category_id = c.category_id
                              WHERE p.is_active = 1
                              ORDER BY total_value DESC
                              LIMIT 10");

// Stock movement by category
$category_stats = $conn->query("SELECT c.category_name, 
                                COUNT(p.product_id) as product_count,
                                SUM(p.quantity_in_stock) as total_stock,
                                SUM(p.quantity_in_stock * p.unit_price) as total_value
                                FROM categories c
                                LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
                                GROUP BY c.category_id
                                ORDER BY total_value DESC");

$unread_alerts = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .navbar, .toolbar, .btn, .no-print { display: none; }
        }
    </style>
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
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
            <h2>Reports & Analytics</h2>
            <div class="breadcrumb">Home / Reports</div>
        </div>

        <!-- Date Range Filter -->
        <div class="card no-print">
            <div class="card-body">
                <form method="GET" style="display: flex; gap: 20px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </form>
            </div>
        </div>

        <!-- Overview Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo formatCurrency($total_value); ?></h3>
                    <p>Inventory Value</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stock_out_count['total'] ?: 0; ?></h3>
                    <p>Stock Out (<?php echo $stock_out_count['count']; ?> transactions)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stock_in_count['total'] ?: 0; ?></h3>
                    <p>Stock In (<?php echo $stock_in_count['count']; ?> transactions)</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px;">
            <!-- Top Products by Value -->
            <div class="card">
                <div class="card-header">
                    <h3>Top 10 Products by Value</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Unit Price</th>
                                    <th>Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $top_products->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo $row['quantity_in_stock']; ?></td>
                                    <td><?php echo formatCurrency($row['unit_price']); ?></td>
                                    <td><strong><?php echo formatCurrency($row['total_value']); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Category Statistics -->
            <div class="card">
                <div class="card-header">
                    <h3>Inventory by Category</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Products</th>
                                    <th>Total Stock</th>
                                    <th>Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $category_stats->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo $row['product_count']; ?></td>
                                    <td><?php echo $row['total_stock'] ?: 0; ?></td>
                                    <td><strong><?php echo formatCurrency($row['total_value'] ?: 0); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Summary -->
        <div class="card">
            <div class="card-header">
                <h3>Alerts Summary</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="padding: 20px; background-color: #fee2e2; border-radius: 8px; text-align: center;">
                        <h2 style="color: #991b1b; font-size: 36px; margin: 0;"><?php echo $low_stock_count; ?></h2>
                        <p style="color: #7f1d1d; margin: 5px 0 0 0;">Low Stock Items</p>
                    </div>
                    <div style="padding: 20px; background-color: #fef3c7; border-radius: 8px; text-align: center;">
                        <h2 style="color: #92400e; font-size: 36px; margin: 0;"><?php echo $expired_count; ?></h2>
                        <p style="color: #78350f; margin: 5px 0 0 0;">Expired Items</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>