<?php
// includes/functions.php

// Clean input data to prevent XSS
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email format
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
}

// Check if user is researcher
function is_researcher() {
    return (isset($_SESSION['role']) && ($_SESSION['role'] == 'researcher' || $_SESSION['role'] == 'admin'));
}

// Get bug report information by ID
function get_report_by_id($conn, $report_id) {
    $sql = "SELECT r.*, u.username as reporter_name, 
            u2.username as assigned_name 
            FROM bug_reports r 
            LEFT JOIN users u ON r.reported_by = u.user_id 
            LEFT JOIN users u2 ON r.assigned_to = u2.user_id 
            WHERE r.report_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $report_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

// Get comments for a bug report
function get_comments_by_report_id($conn, $report_id) {
    $sql = "SELECT c.*, u.username FROM comments c 
            JOIN users u ON c.user_id = u.user_id 
            WHERE c.report_id = ? 
            ORDER BY c.created_at ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $report_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $comments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    
    return $comments;
}

// Convert severity to label class
function get_severity_class($severity) {
    switch ($severity) {
        case 'critical':
            return 'danger';
        case 'high':
            return 'warning';
        case 'medium':
            return 'info';
        case 'low':
            return 'success';
        default:
            return 'secondary';
    }
}

// Convert status to label class
function get_status_class($status) {
    switch ($status) {
        case 'new':
            return 'primary';
        case 'in_progress':
            return 'info';
        case 'fixed':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'duplicate':
            return 'warning';
        default:
            return 'secondary';
    }
}

// Get count of bug reports by status
function get_report_counts_by_status($conn) {
    $sql = "SELECT status, COUNT(*) AS count FROM bug_reports GROUP BY status";
    $result = mysqli_query($conn, $sql);
    
    $counts = [
        'new' => 0,
        'in_progress' => 0,
        'fixed' => 0,
        'rejected' => 0,
        'duplicate' => 0
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $counts[$row['status']] = $row['count'];
    }
    
    return $counts;
}

// Get count of bug reports by severity
function get_report_counts_by_severity($conn) {
    $sql = "SELECT severity, COUNT(*) AS count FROM bug_reports GROUP BY severity";
    $result = mysqli_query($conn, $sql);
    
    $counts = [
        'low' => 0,
        'medium' => 0,
        'high' => 0,
        'critical' => 0
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $counts[$row['severity']] = $row['count'];
    }
    
    return $counts;
}

// Upload file
function upload_file($file) {
    $target_dir = "../assets/uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return ["error" => "File is too large. Maximum size is 5MB."];
    }
    
    // Allow only certain file formats
    $allowed_extensions = ["jpg", "jpeg", "png", "gif", "pdf", "doc", "docx", "txt", "zip"];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ["error" => "Only JPG, JPEG, PNG, GIF, PDF, DOC, DOCX, TXT, and ZIP files are allowed."];
    }
    
    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $new_filename, "path" => "assets/uploads/" . $new_filename];
    } else {
        return ["error" => "Failed to upload file."];
    }
}

// Function to generate pagination links
function generate_pagination($current_page, $total_pages, $url) {
    $links = '';
    
    $links .= '<ul class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $links .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $links .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $links .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $links .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><a class="page-link" href="#">Next</a></li>';
    }
    
    $links .= '</ul>';
    
    return $links;
}
?>