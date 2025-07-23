<?php
// submit_report.php
require_once 'includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$title = $description = $severity = "";
$error = "";

// Process report submission form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    if (empty($_POST["title"])) {
        $error = "Title is required";
    } else {
        $title = clean_input($_POST["title"]);
    }
    
    // Validate description
    if (empty($_POST["description"])) {
        $error = "Description is required";
    } else {
        $description = clean_input($_POST["description"]);
    }
    
    // Validate severity
    if (empty($_POST["severity"])) {
        $error = "Severity is required";
    } else {
        $severity = clean_input($_POST["severity"]);
        if (!in_array($severity, ["low", "medium", "high", "critical"])) {
            $error = "Invalid severity level";
        }
    }
    
    // Handle file upload
    $attachment_path = null;
    if (!empty($_FILES["attachment"]["name"])) {
        $upload_result = upload_file($_FILES["attachment"]);
        if (isset($upload_result["error"])) {
            $error = $upload_result["error"];
        } else {
            $attachment_path = $upload_result["path"];
        }
    }
    
    // Submit report if no errors
    if (empty($error)) {
        $user_id = $_SESSION['user_id'];
        $status = "new";
        
        $sql = "INSERT INTO bug_reports (title, description, severity, status, reported_by, attachment_path) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $title, $description, $severity, $status, $user_id, $attachment_path);
        
        if (mysqli_stmt_execute($stmt)) {
            $report_id = mysqli_insert_id($conn);
            $_SESSION['success_message'] = "Bug report submitted successfully!";
            header("Location: view_report.php?id=" . $report_id);
            exit();
        } else {
            $error = "Error submitting report. Please try again.";
        }
    }
}
?>

<h2>Submit Bug Report</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5>Report Details</h5>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
                <div class="form-text">Provide a clear and concise title for the bug.</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="6" required><?php echo $description; ?></textarea>
                <div class="form-text">
                    Describe the bug in detail. Include steps to reproduce, expected behavior, and actual behavior.
                </div>
            </div>
            <div class="mb-3">
                <label for="severity" class="form-label">Severity</label>
                <select class="form-select" id="severity" name="severity" required>
                    <option value="" disabled <?php echo empty($severity) ? 'selected' : ''; ?>>Select severity level</option>
                    <option value="low" <?php echo $severity == 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $severity == 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $severity == 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $severity == 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
                <div class="form-text">
                    <strong>Low:</strong> Minor issues with minimal impact.<br>
                    <strong>Medium:</strong> Issues that have some impact but don't expose sensitive data.<br>
                    <strong>High:</strong> Significant vulnerabilities that may expose user data.<br>
                    <strong>Critical:</strong> Severe vulnerabilities that can lead to system compromise.
                </div>
            </div>
            <div class="mb-3">
                <label for="attachment" class="form-label">Attachment (Optional)</label>
                <input type="file" class="form-control" id="attachment" name="attachment">
                <div class="form-text">Upload screenshots, proof-of-concept files, or other relevant documents (Max size: 5MB).</div>
            </div>
            <button type="submit" class="btn btn-primary">Submit Report</button>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>