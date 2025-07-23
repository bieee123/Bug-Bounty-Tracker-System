<?php
// /admin/manage_reports.php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';
require_once '../config/db_connect.php'; 
require_once '../includes/admin_functions.php';

// Define statuses and categories
$statuses = ['new', 'in_progress', 'resolved', 'closed', 'reopened'];
$categories = [];

// Get all categories from the database
$cat_sql = "SELECT category_id as id, category_name as name FROM categories ORDER BY category_name";
$cat_result = mysqli_query($conn, $cat_sql);
if ($cat_result) {
    $categories = mysqli_fetch_all($cat_result, MYSQLI_ASSOC);
    mysqli_free_result($cat_result);
}

// Define $search safely
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Redirect if not admin
if (!is_admin()) {
    $_SESSION['error_message'] = "Access denied. Admin privileges required.";
    header("Location: ../index.php");
    exit();
}

// Process report actions if any
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update report status
    if (isset($_POST['update_status'])) {
        $report_id = (int)$_POST['report_id'];
        $new_status = clean_input($_POST['status']); // Ensure you sanitize input
    
        // SQL query to update the report status
        $sql = "UPDATE bug_reports SET status = ? WHERE report_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $report_id);
    
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Report status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating report status: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    
        header("Location: manage_reports.php"); // Redirect after update
        exit();
    }
    
    // Delete report
    if (isset($_POST['delete_report'])) {
        $report_id = (int)$_POST['report_id'];
    
        // SQL query to delete the report
        $sql = "DELETE FROM bug_reports WHERE report_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $report_id);
    
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Report deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting report: " . mysqli_error($conn);
        }
    
        mysqli_stmt_close($stmt);
    
        header("Location: manage_reports.php"); // Redirect after deletion
        exit();
    }    
    
    // Redirect after action
    header("Location: manage_reports.php");
    exit();
}

// Check if a specific status is passed via the URL
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build the base SQL query
$sql = "SELECT r.report_id AS id, r.title, r.description, r.status, r.priority, r.date_submitted, 
        u.username, u.user_id, c.category_name, c.category_id
        FROM bug_reports r
        JOIN users u ON r.reported_by = u.user_id
        JOIN categories c ON r.category_id = c.category_id
        WHERE 1=1";

$params = [];
$types = "";

// Add status filter if selected
if (!empty($status_filter)) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add category filter if selected
if ($category_filter > 0) {
    $sql .= " AND r.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

// Add search filter if entered
if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR u.username LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Order by date, newest first
$sql .= " ORDER BY r.date_submitted DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$page_title = "Manage Reports";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Reports</h1>
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
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo ($status_filter == $status) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ((int)$category_filter == (int)$category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search reports..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="manage_reports.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reports Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="alert alert-info">No reports found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Submitter</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?php echo $report['id']; ?></td>
                                    <td>
                                        <a href="../view_report.php?id=<?php echo $report['report_id']; ?>">
                                            <?php echo htmlspecialchars($report['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                                    <td>
                                        <a href="../view_report.php?id=<?php echo $report['id']; ?>">
                                            <?php echo htmlspecialchars($status['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo isset($report['date_submitted']) ? date('M d, Y', strtotime($report['date_submitted'])) : 'Unknown'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo get_status_color($report['status']); ?>">
                                            <?php echo ucfirst($report['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo get_priority_color($report['priority']); ?>">
                                            <?php echo ucfirst($report['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionDropdown<?php echo $report['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="actionDropdown<?php echo $report['id']; ?>">
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>