<?php
require_once 'config.php';
checkRole(['admin']);

$conn = getDBConnection();

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$unread_alerts = getUnreadAlertsCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
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
                <li><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
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
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h2>User Management</h2>
            <div class="breadcrumb">Home / Users</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>System Users</h3>
                <button class="btn btn-primary" onclick="showAddUserModal()">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo ucfirst($row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['last_login'] ? formatDate($row['last_login']) : 'Never'; ?></td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $row['user_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-<?php echo $row['is_active'] ? 'danger' : 'success'; ?>" 
                                            onclick="toggleUserStatus(<?php echo $row['user_id']; ?>, <?php echo $row['is_active']; ?>)">
                                        <i class="fas fa-<?php echo $row['is_active'] ? 'ban' : 'check'; ?>"></i>
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

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New User</h3>
                <span class="close" onclick="closeModal('userModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm" method="POST" action="process_user.php">
                    <input type="hidden" id="user_id" name="user_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="admin">Administrator</option>
                                <option value="pharmacist">Pharmacist</option>
                                <option value="doctor">Doctor</option>
                                <option value="nurse">Nurse</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control">
                            <small>Leave blank to keep current password (for edit)</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitUser()">Save User</button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/users.js"></script>
</body>
</html>
<?php $conn->close(); ?>