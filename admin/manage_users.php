<?php
// /admin/manage_users.php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../config/db_connect.php'; 
require_once '../includes/admin_functions.php';

// Redirect if not admin
if (!is_admin()) {
    $_SESSION['error_message'] = "Access denied. Admin privileges required.";
    header("Location: ../index.php");
    exit();
}

// Process user actions if any
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Change user status
    if (isset($_POST['change_status'])) {
        // Get user ID and new status
        $user_id = (int)$_POST['user_id'];
        $new_status = clean_input($_POST['status']);
    
        // Check valid status
        $valid_statuses = array('active', 'inactive', 'banned');
        if (!in_array($new_status, $valid_statuses)) {
            $_SESSION['error_message'] = "Invalid status value.";
            header("Location: manage_users.php");
            exit();
        }
    
        // SQL query to update user status
        $sql = "UPDATE users SET status = ? WHERE user_id = ?";
    
        // Prepare the query
        $stmt = mysqli_prepare($conn, $sql);
        
        // Check if the query was prepared successfully
        if ($stmt === false) {
            $_SESSION['error_message'] = "Error preparing the query: " . mysqli_error($conn);
            header("Location: manage_users.php");
            exit();
        }
    
        // Bind parameters and execute the query
        mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
    
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "User status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating user status: " . mysqli_error($conn);
        }
    
        // Close the statement
        mysqli_stmt_close($stmt);
    
        // Redirect after updating
        header("Location: manage_users.php");
        exit();
    }                                           
    
    // Change user role
    if (isset($_POST['change_role'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = clean_input($_POST['role']);
        $valid_roles = array('user', 'admin', 'researcher'); // Include 'researcher' as a valid role
    
        if (!in_array($new_role, $valid_roles)) {
            $_SESSION['error_message'] = "Invalid role value.";
            header("Location: manage_users.php");
            exit();
        }
    
        // Set default status based on role
        $new_status = 'inactive'; // Default status for researcher
        if ($new_role == 'admin') {
            $new_status = 'active'; // Admins should be active
        } elseif ($new_role == 'user') {
            $new_status = 'inactive'; // Users will default to inactive
        }
    
        // SQL query to update the role and status
        $sql = "UPDATE users SET role = ?, status = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_role, $new_status, $user_id);
    
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "User role and status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating user role and status: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }    
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Don't allow deleting the current admin
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "Cannot delete your own account.";
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "User deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Redirect after action
    header("Location: manage_users.php");
    exit();
}

// Get search and filters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? clean_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Get users with filters
$users = get_filtered_users($conn, $search, $role_filter, $status_filter);

$page_title = "Manage Users";
include_once '../includes/header.php';
?>

<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Users</h1>
        <a href="index.php" class="btn btn-secondary">Back to Admin Dashboard</a>
    </div>
    
    <!-- Display error/success messages if any -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo ($role_filter == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($role_filter == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="researcher" <?php echo ($role_filter == 'researcher') ? 'selected' : ''; ?>>Researcher</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="banned" <?php echo ($status_filter == 'banned') ? 'selected' : ''; ?>>Banned</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="manage_users.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info">No users found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Reports</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if (isset($report['report_id'])) {
                                            echo htmlspecialchars($report['report_id']);
                                        } else {
                                            echo "ID not available";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo isset($user['role']) ? get_role_color_user($user['role']) : 'default'; ?>">
                                            <?php echo isset($user['role']) ? ucfirst($user['role']) : 'Unknown'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo isset($user['status']) ? get_status_color_user($user['status']) : 'default'; ?>">
                                            <?php echo isset($user['status']) ? ucfirst($user['status']) : 'Unknown'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($user['user_id'])) {                                           
                                            $count = get_user_report_count($conn, $user['user_id']);
                                            echo $count;
                                        } else {
                                            echo "User ID not available";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userActionDropdown<?php echo isset($user['id']) ? $user['id'] : 'default'; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu" aria-labelledby="userActionDropdown<?php echo isset($user['id']) ? $user['id'] : 'default'; ?>">
                                                <li>
                                                    <a class="dropdown-item" href="user_details.php?id=<?php echo isset($user['id']) ? $user['id'] : ''; ?>">
                                                        <i class="fas fa-user"></i> View Details
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeRoleModal<?php echo isset($user['id']) ? $user['id'] : ''; ?>">
                                                        <i class="fas fa-user-tag"></i> Change Role
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeStatusModal<?php echo isset($user['id']) ? $user['id'] : ''; ?>">
                                                        <i class="fas fa-toggle-on"></i> Change Status
                                                    </button>
                                                </li>
                                                <?php if (isset($user['id']) && $user['id'] != $_SESSION['user_id']): ?>
                                                <li>
                                                    <?php if (isset($user['id']) && $user['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo isset($user['id']) ? $user['id'] : ''; ?>">
                                                            <i class="fas fa-trash">Delete</i> 
                                                        </button>
                                                    <?php endif; ?>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Change Role Modal -->
                                        <div class="modal fade" id="changeRoleModal<?php echo isset($user['id']) ? $user['id'] : ''; ?>" tabindex="-1" aria-labelledby="changeRoleModalLabel<?php echo isset($user['id']) ? $user['id'] : ''; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="changeRoleModalLabel<?php echo isset($user['id']) ? $user['id'] : ''; ?>">Change User Role</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="user_id" value="<?php echo isset($user['id']) ? $user['id'] : ''; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">User: <strong><?php echo htmlspecialchars($user['username']); ?></strong></label>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="role" class="form-label">New Role</label>
                                                                <select name="role" class="form-control" required>
                                                                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                                    <option value="researcher" <?php echo ($user['role'] == 'researcher') ? 'selected' : ''; ?>>Researcher</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="change_role" class="btn btn-primary">Update Role</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Change Status Modal -->
                                        <div class="modal fade" id="changeStatusModal<?php echo isset($user['id']) ? $user['id'] : ''; ?>" tabindex="-1" aria-labelledby="changeStatusModalLabel<?php echo isset($user['id']) ? $user['id'] : ''; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="changeStatusModalLabel<?php echo isset($user['id']) ? $user['id'] : ''; ?>">Change User Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="user_id" value="<?php echo isset($user['id']) ? $user['id'] : ''; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">User: <strong><?php echo htmlspecialchars($user['username']); ?></strong></label>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="status" class="form-label">New Status</label>
                                                                <select name="status" id="status" class="form-control" required>
                                                                    <option value="active" <?php echo (isset($user['status']) && $user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                                    <option value="inactive" <?php echo (isset($user['status']) && $user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                                    <option value="banned" <?php echo (isset($user['status']) && $user['status'] == 'banned') ? 'selected' : ''; ?>>Banned</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="change_status" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <?php if (isset($user['id']) && $user['id'] != $_SESSION['user_id']): ?>
                                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete user: <strong><?php echo htmlspecialchars($user['username']); ?></strong>?
                                                            <p class="text-danger mt-2">This action cannot be undone. All user's reports and comments will also be deleted.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <form method="POST">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>