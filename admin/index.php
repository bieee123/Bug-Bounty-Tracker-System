<?php
// /admin/index.php
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

// Get dashboard statistics
$total_users = get_total_users($conn);
$total_reports = get_total_reports($conn);
$open_reports = count(get_reports_by_status($conn, 'Open'));
$recent_reports = get_recent_reports($conn, 5);
$latest_users = get_latest_users($conn, 5);

$page_title = "Admin Dashboard";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Admin Dashboard</h1>
    
    <!-- Summary Cards -->
    <div class="row mt-4">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h1 class="display-4"><?php echo $total_users; ?></h1>
                    <a href="manage_users.php" class="text-white">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Reports</h5>
                    <h1 class="display-4"><?php echo $total_reports; ?></h1>
                    <a href="manage_reports.php" class="text-white">Manage Reports</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Open Reports</h5>
                    <h1 class="display-4"><?php echo $open_reports; ?></h1>
                    <a href="manage_reports.php?status=Open" class="text-dark">View Open Reports</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Categories</h5>
                    <h1 class="display-4"><?php echo count(get_all_categories($conn)); ?></h1>
                    <a href="manage_categories.php" class="text-white">Manage Categories</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Reports -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Recent Reports</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_reports)): ?>
                <p class="text-muted">No reports found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reports as $report): ?>
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
                                    <td><?php echo htmlspecialchars($report['title']); ?></td>
                                    <td>
                                        <?php echo isset($report['reporter_name']) ? htmlspecialchars($report['reporter_name']) : 'N/A'; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?php echo get_status_class($report['status']); ?>"><?php echo str_replace('_', ' ', ucfirst($report['status'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userActionDropdown<?php echo isset($user['id']) ? $user['id'] : 'default'; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="userActionDropdown<?php echo isset($user['id']) ? $user['id'] : 'default'; ?>">
                                                <li>
                                                <a class="dropdown-item" href="view_report_details.php?id=<?php echo $report['id']; ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="edit_report.php?id=<?php echo $report['id']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeStatusModal<?php echo $report['id']; ?>">
                                                        <i class="fas fa-exchange-alt"></i> Change Status
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $report['id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        
                                        <!-- Change Status Modal -->
                                        <div class="modal fade" id="changeStatusModal<?php echo $report['id']; ?>" tabindex="-1" aria-labelledby="changeStatusModalLabel<?php echo $report['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="changeStatusModalLabel<?php echo $report['id']; ?>">Change Report Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="status" class="form-label">New Status</label>
                                                                <select name="status" id="status" class="form-control" required>
                                                                    <?php foreach ($statuses as $status): ?>
                                                                        <option value="<?php echo $status; ?>" <?php echo ($report['status'] == $status) ? 'selected' : ''; ?>>
                                                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $report['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $report['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $report['id']; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete this report: <strong><?php echo htmlspecialchars($report['title']); ?></strong>?
                                                        <p class="text-danger mt-2">This action cannot be undone. All associated comments will also be deleted.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST">
                                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                            <button type="submit" name="delete_report" class="btn btn-danger">Delete Report</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <a href="manage_reports.php" class="btn btn-primary">View All Reports</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Latest Users -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Latest Users</h5>
        </div>
        <div class="card-body">
            <?php if (empty($latest_users)): ?>
                <p class="text-muted">No users found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latest_users as $user): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if (isset($user['user_id'])) {
                                            echo htmlspecialchars($user['user_id']);
                                        } else {
                                            echo "ID not available";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?php echo get_role_color_user($user['role']); ?>">
                                            <?php echo str_replace('_', ' ', ucfirst($user['role'])); ?>
                                        </span>
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
                <div class="text-end">
                    <a href="manage_users.php" class="btn btn-primary">View All Users</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>