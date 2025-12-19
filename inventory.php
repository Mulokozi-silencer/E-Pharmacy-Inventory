<?php
require_once 'config.php';
checkLogin();

$conn = getDBConnection();

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? sanitize($conn, $_GET['search']) : '';

// Build query
$where_clause = "WHERE p.is_active = 1";

if ($filter == 'low_stock') {
    $where_clause .= " AND p.quantity_in_stock <= p.reorder_level";
} elseif ($filter == 'expiring') {
    $where_clause .= " AND p.expiry_date IS NOT NULL AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND p.expiry_date > CURDATE()";
} elseif ($filter == 'expired') {
    $where_clause .= " AND p.expiry_date IS NOT NULL AND p.expiry_date <= CURDATE()";
}

if ($search) {
    $where_clause .= " AND (p.product_name LIKE '%$search%' OR p.product_code LIKE '%$search%')";
}

$query = "SELECT p.*, c.category_name, s.supplier_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
          $where_clause 
          ORDER BY p.product_name";

$products = $conn->query($query);

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");

$unread_alerts = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - <?php echo SITE_NAME; ?></title>
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
                <li><a href="inventory.php" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
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
            <h2>Inventory Management</h2>
            <div class="breadcrumb">Home / Inventory</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Product Inventory</h3>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'pharmacist'): ?>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <select id="filterSelect" class="form-control" style="width: 200px;">
                            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Products</option>
                            <option value="low_stock" <?php echo $filter == 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="expiring" <?php echo $filter == 'expiring' ? 'selected' : ''; ?>>Expiring Soon</option>
                            <option value="expired" <?php echo $filter == 'expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                        
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-container">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td>
                                    <?php 
                                    if ($row['quantity_in_stock'] <= $row['reorder_level']) {
                                        echo '<span class="badge badge-danger">' . $row['quantity_in_stock'] . '</span>';
                                    } else {
                                        echo '<span class="badge badge-success">' . $row['quantity_in_stock'] . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatCurrency($row['unit_price']); ?></td>
                                <td>
                                    <?php 
                                    if ($row['expiry_date']) {
                                        $expiry = strtotime($row['expiry_date']);
                                        $today = strtotime(date('Y-m-d'));
                                        if ($expiry < $today) {
                                            echo '<span class="badge badge-danger">' . formatDate($row['expiry_date']) . '</span>';
                                        } elseif ($expiry <= strtotime('+30 days')) {
                                            echo '<span class="badge badge-warning">' . formatDate($row['expiry_date']) . '</span>';
                                        } else {
                                            echo formatDate($row['expiry_date']);
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($row['quantity_in_stock'] <= 0) {
                                        echo '<span class="badge badge-danger">Out of Stock</span>';
                                    } elseif ($row['quantity_in_stock'] <= $row['reorder_level']) {
                                        echo '<span class="badge badge-warning">Low Stock</span>';
                                    } else {
                                        echo '<span class="badge badge-success">In Stock</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewProduct(<?php echo $row['product_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'pharmacist'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="editProduct(<?php echo $row['product_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $row['product_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Product</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST" action="process_product.php">
                    <input type="hidden" id="product_id" name="product_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_code">Product Code *</label>
                            <input type="text" id="product_code" name="product_code" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="supplier_id">Supplier *</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value="">Select Supplier</option>
                                <?php 
                                $suppliers->data_seek(0);
                                while ($sup = $suppliers->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $sup['supplier_id']; ?>"><?php echo htmlspecialchars($sup['supplier_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="unit_price">Unit Price *</label>
                            <input type="number" id="unit_price" name="unit_price" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity_in_stock">Quantity in Stock *</label>
                            <input type="number" id="quantity_in_stock" name="quantity_in_stock" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reorder_level">Reorder Level *</label>
                            <input type="number" id="reorder_level" name="reorder_level" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="unit_of_measure">Unit of Measure</label>
                            <input type="text" id="unit_of_measure" name="unit_of_measure" class="form-control" placeholder="e.g., Tablets, Boxes">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="date" id="expiry_date" name="expiry_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="batch_number">Batch Number</label>
                            <input type="text" id="batch_number" name="batch_number" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="storage_location">Storage Location</label>
                            <input type="text" id="storage_location" name="storage_location" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="requires_prescription" name="requires_prescription" value="1">
                                Requires Prescription
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitProduct()">Save Product</button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/inventory.js"></script>
</body>
</html>
<?php $conn->close(); ?>