<?php
// dashboard.php
require_once 'includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Get counts for dashboard stats
$status_counts = get_report_counts_by_status($conn);
$severity_counts = get_report_counts_by_severity($conn);
$total_reports = array_sum($status_counts);

// Get reports submitted by current user
$user_id = $_SESSION['user_id'];
$user_reports_query = "SELECT * FROM bug_reports WHERE reported_by = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $user_reports_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_reports_result = mysqli_stmt_get_result($stmt);

// Get recent reports (visible to all)
$recent_reports_query = "SELECT r.*, u.username as reporter_name 
                         FROM bug_reports r 
                         JOIN users u ON r.reported_by = u.user_id 
                         ORDER BY r.created_at DESC LIMIT 5";
$recent_reports_result = mysqli_query($conn, $recent_reports_query);
?>

<h2>Dashboard</h2>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Reports</h5>
                <h2 class="card-text"><?php echo $total_reports; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Fixed</h5>
                <h2 class="card-text"><?php echo $status_counts['fixed']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">In Progress</h5>
                <h2 class="card-text"><?php echo $status_counts['in_progress']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Critical Severity</h5>
                <h2 class="card-text"><?php echo $severity_counts['critical']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Your Recent Reports</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($user_reports_result) > 0): ?>
                    <div class="list-group">
                        <?php while ($report = mysqli_fetch_assoc($user_reports_result)): ?>
                            <a href="view_report.php?id=<?php echo $report['report_id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h5>
                                    <small><?php echo date('M d, Y', strtotime($report['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo substr(htmlspecialchars($report['description']), 0, 100) . '...'; ?></p>
                                <div>
                                    <span class="badge rounded-pill bg-<?php echo get_severity_class($report['severity']); ?>"><?php echo ucfirst($report['severity']); ?></span>
                                    <span class="badge rounded-pill bg-<?php echo get_status_class($report['status']); ?>"><?php echo str_replace('_', ' ', ucfirst($report['status'])); ?></span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                    <div class="mt-3">
                        <a href="submit_report.php" class="btn btn-primary">Submit New Report</a>
                    </div>
                <?php else: ?>
                    <p>You haven't submitted any reports yet.</p>
                    <a href="submit_report.php" class="btn btn-primary">Submit Your First Report</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Reports</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($recent_reports_result) > 0): ?>
                    <div class="list-group">
                        <?php while ($report = mysqli_fetch_assoc($recent_reports_result)): ?>
                            <a href="view_report.php?id=<?php echo $report['report_id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h5>
                                    <small><?php echo date('M d, Y', strtotime($report['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo substr(htmlspecialchars($report['description']), 0, 100) . '...'; ?></p>
                                <div>
                                    <span class="badge rounded-pill bg-<?php echo get_severity_class($report['severity']); ?>"><?php echo ucfirst($report['severity']); ?></span>
                                    <span class="badge rounded-pill bg-<?php echo get_status_class($report['status']); ?>"><?php echo str_replace('_', ' ', ucfirst($report['status'])); ?></span>
                                    <small class="text-muted">by <?php echo htmlspecialchars($report['reporter_name']); ?></small>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No reports available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>