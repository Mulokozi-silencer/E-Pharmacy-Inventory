<?php
require_once 'config.php';
checkLogin();

$conn = getDBConnection();

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';

// Build query
$where_clause = "WHERE 1=1";

if ($filter == 'in') {
    $where_clause .= " AND st.transaction_type = 'in'";
} elseif ($filter == 'out') {
    $where_clause .= " AND st.transaction_type = 'out'";
}

if ($search) {
    $where_clause .= " AND (p.product_name LIKE '%$search%' OR u.full_name LIKE '%$search%' OR st.reference_number LIKE '%$search%')";
}

$query = "SELECT st.*, p.product_name, p.product_code, u.full_name 
          FROM stock_transactions st 
          JOIN products p ON st.product_id = p.product_id 
          JOIN users u ON st.user_id = u.user_id 
          $where_clause 
          ORDER BY st.transaction_date DESC 
          LIMIT 100";

$transactions = $conn->query($query);

// Get products for dropdown
$products = $conn->query("SELECT product_id, product_name, product_code, quantity_in_stock FROM products WHERE is_active = 1 ORDER BY product_name");

$unread_alerts = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transactions - <?php echo SITE_NAME; ?></title>
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
                <li><a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
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
            <h2>Stock Transactions</h2>
            <div class="breadcrumb">Home / Transactions</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Transaction History</h3>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'pharmacist'): ?>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success" onclick="showStockInModal()">
                        <i class="fas fa-plus"></i> Stock In
                    </button>
                    <button class="btn btn-danger" onclick="showStockOutModal()">
                        <i class="fas fa-minus"></i> Stock Out
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search transactions..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <select id="filterSelect" class="form-control" style="width: 200px;">
                            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Transactions</option>
                            <option value="in" <?php echo $filter == 'in' ? 'selected' : ''; ?>>Stock In</option>
                            <option value="out" <?php echo $filter == 'out' ? 'selected' : ''; ?>>Stock Out</option>
                        </select>
                        
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>User</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($row['transaction_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td>
                                    <?php if ($row['transaction_type'] == 'in'): ?>
                                        <span class="badge badge-success">Stock In</span>
                                    <?php elseif ($row['transaction_type'] == 'out'): ?>
                                        <span class="badge badge-danger">Stock Out</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Adjustment</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($row['reference_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo $row['patient_name'] ? htmlspecialchars($row['patient_name']) : '-'; ?></td>
                                <td><?php echo $row['doctor_name'] ? htmlspecialchars($row['doctor_name']) : '-'; ?></td>
                                <td><?php echo $row['notes'] ? htmlspecialchars(substr($row['notes'], 0, 50)) : '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock In Modal -->
    <div id="stockInModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Stock In</h3>
                <span class="close" onclick="closeModal('stockInModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="stockInForm" method="POST" action="process_transaction.php">
                    <input type="hidden" name="transaction_type" value="in">
                    
                    <div class="form-group">
                        <label for="product_id_in">Product *</label>
                        <select id="product_id_in" name="product_id" class="form-control" required>
                            <option value="">Select Product</option>
                            <?php 
                            $products->data_seek(0);
                            while ($prod = $products->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $prod['product_id']; ?>">
                                <?php echo htmlspecialchars($prod['product_name']); ?> 
                                (<?php echo $prod['product_code']; ?>) - 
                                Current: <?php echo $prod['quantity_in_stock']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_in">Quantity *</label>
                        <input type="number" id="quantity_in" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_in">Reference Number</label>
                        <input type="text" id="reference_in" name="reference_number" class="form-control" placeholder="e.g., PO-12345">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes_in">Notes</label>
                        <textarea id="notes_in" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('stockInModal')">Cancel</button>
                <button class="btn btn-success" onclick="submitStockIn()">Add Stock</button>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal -->
    <div id="stockOutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Stock Out</h3>
                <span class="close" onclick="closeModal('stockOutModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="stockOutForm" method="POST" action="process_transaction.php">
                    <input type="hidden" name="transaction_type" value="out">
                    
                    <div class="form-group">
                        <label for="product_id_out">Product *</label>
                        <select id="product_id_out" name="product_id" class="form-control" required>
                            <option value="">Select Product</option>
                            <?php 
                            $products->data_seek(0);
                            while ($prod = $products->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $prod['product_id']; ?>">
                                <?php echo htmlspecialchars($prod['product_name']); ?> 
                                (<?php echo $prod['product_code']; ?>) - 
                                Available: <?php echo $prod['quantity_in_stock']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_out">Quantity *</label>
                        <input type="number" id="quantity_out" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="patient_name">Patient Name</label>
                        <input type="text" id="patient_name" name="patient_name" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="doctor_name">Doctor Name</label>
                        <input type="text" id="doctor_name" name="doctor_name" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_out">Reference Number</label>
                        <input type="text" id="reference_out" name="reference_number" class="form-control" placeholder="e.g., RX-12345">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes_out">Notes</label>
                        <textarea id="notes_out" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('stockOutModal')">Cancel</button>
                <button class="btn btn-danger" onclick="submitStockOut()">Remove Stock</button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/transactions.js"></script>
</body>
</html>
<?php $conn->close(); ?>