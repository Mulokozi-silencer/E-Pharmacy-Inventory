<?php
require_once 'config.php';
checkRole(['admin', 'pharmacist']);

$conn = getDBConnection();

$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name");

$unread_alerts = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers Management - <?php echo SITE_NAME; ?></title>
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
                <li><a href="suppliers.php" class="active"><i class="fas fa-truck"></i> Suppliers</a></li>
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
            <h2>Suppliers Management</h2>
            <div class="breadcrumb">Home / Suppliers</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Supplier List</h3>
                <button class="btn btn-primary" onclick="showAddSupplierModal()">
                    <i class="fas fa-plus"></i> Add New Supplier
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $suppliers->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['address'], 0, 50)); ?></td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editSupplier(<?php echo $row['supplier_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-<?php echo $row['is_active'] ? 'danger' : 'success'; ?>" 
                                            onclick="toggleSupplierStatus(<?php echo $row['supplier_id']; ?>, <?php echo $row['is_active']; ?>)">
                                        <i class="fas fa-<?php echo $row['is_active'] ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Supplier</h3>
                <span class="close" onclick="closeModal('supplierModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="supplierForm" method="POST" action="process_supplier.php">
                    <input type="hidden" id="supplier_id" name="supplier_id">
                    
                    <div class="form-group">
                        <label for="supplier_name">Supplier Name *</label>
                        <input type="text" id="supplier_name" name="supplier_name" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_person">Contact Person</label>
                            <input type="text" id="contact_person" name="contact_person" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('supplierModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/suppliers.js"></script>
</body>
</html>
<?php $conn->close(); ?>