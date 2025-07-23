<?php
// admin_functions.php - Contains all admin-specific functions

// Pastikan database koneksi sudah dibuat
if (!isset($conn)) {
    // Jika koneksi belum dibuat, coba ambil dari file db_connect.php
    if (file_exists(__DIR__ . '/db_connect.php')) {
        require_once __DIR__ . '/db_connect.php';
    } elseif (file_exists(__DIR__ . '/../includes/db_connect.php')) {
        require_once __DIR__ . '/../includes/db_connect.php';
    }
}

// Function to get total users
function get_total_users($conn) {
    if (!$conn) {
        return 0; // Return 0 if no connection available
    }
    
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = mysqli_query($conn, $sql);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    
    return 0;
}

// Function to get total reports
function get_total_reports($conn) {
    // Check if the connection is valid
    if (!$conn) {
        return 0; // Return 0 if no connection available
    }

    // Query to get the total number of reports from the database
    $sql = "SELECT COUNT(*) as total_reports FROM bug_reports";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total_reports']; // Return the total count
    } else {
        // Log the error if the query fails
        error_log("Error fetching total reports: " . mysqli_error($conn));
        return 0; // Return 0 if the query fails
    }
}

// Function to get reports by status
function get_reports_by_status($conn, $status) {
    // Ensure connection is valid
    if (!$conn) {
        return [];
    }

    // Updated query using the correct table name 'bug_reports'
    $sql = "SELECT * FROM bug_reports WHERE status = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind the parameter
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the results
        $reports = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reports[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $reports;
    } else {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return [];
    }
}

// Function to get recent reports
function get_recent_reports($conn, $limit = 5) {
    // Ensure the connection is valid
    if (!$conn) {
        return []; // Return an empty array if no connection available
    }

    // Modify query to join with users table to get reporter username
    $sql = "SELECT r.*, u.username as reporter_name 
            FROM bug_reports r
            LEFT JOIN users u ON r.reported_by = u.user_id
            ORDER BY r.created_at DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind the limit parameter to the statement
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the results and store them in an array
        $reports = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reports[] = $row; // Add each report to the array
        }

        mysqli_stmt_close($stmt);
        return $reports; // Return the array of reports
    } else {
        // Log an error if the query fails
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return []; // Return an empty array if the query fails
    }
}

// Function to get latest users
function get_latest_users($conn, $limit) {
    if (!$conn) {
        return []; // Return empty array if no connection available
    }
    
    $sql = "SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $users;
}

// Function to get user by ID (renamed to avoid conflict with auth_functions.php)
function get_admin_user_by_id($conn, $user_id) {
    // Check if the connection is valid
    if (!$conn) {
        error_log("Database connection failed.");
        return null; // Return null if no connection available
    }
    
    // Prepare the SQL statement
    $sql = "SELECT * FROM users WHERE user_id = ?"; // Ensure column name is correct
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind the parameter
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            // Check if a row is returned
            if ($row = mysqli_fetch_assoc($result)) {
                mysqli_stmt_close($stmt);
                return $row; // Return the user row
            } else {
                mysqli_stmt_close($stmt);
                return null; // No matching user found, return null
            }
        } else {
            error_log("Failed to execute statement: " . mysqli_error($conn));
            mysqli_stmt_close($stmt);
            return null; // Return null if execution fails
        }
    } else {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return null; // Return null if preparation fails
    }
}

// Function to get user report count
function get_user_report_count($conn, $user_id) {
    // Ensure the connection is valid
    if (!$conn || !$user_id) {
        return 0; // Return 0 if no connection or user_id is provided
    }

    // Updated query using 'bug_reports' instead of 'reports'
    $sql = "SELECT COUNT(*) FROM bug_reports WHERE reported_by = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind the user_id parameter
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_row($result);
        mysqli_stmt_close($stmt);
        return $row[0]; // Return the count
    } else {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return 0; // Return 0 if the query fails
    }
}

// Function to update user admin status
function update_user_admin_status($conn, $user_id, $is_admin) {
    if (!$conn) {
        return false; // Return false if no connection available
    }
    
    $sql = "UPDATE users SET is_admin = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $is_admin, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Function to delete user
function delete_user($conn, $user_id) {
    if (!$conn) {
        return false; // Return false if no connection available
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete user's comments
        $sql = "DELETE FROM comments WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Delete user's reports
        $sql = "DELETE FROM reports WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Delete the user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Commit the transaction
        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        // Rollback in case of error
        mysqli_rollback($conn);
        return false;
    }
}

// Function to get filtered reports
function get_filtered_reports($conn, $status, $severity, $assigned_to, $search) {
    if (!$conn) {
        return []; // Return empty array if no connection available
    }

    // Correct query to join comments with users and fetch the user's full name
    $sql = "SELECT r.*, c.comment, u.full_name as commenter_name
            FROM bug_reports r
            LEFT JOIN comments c ON r.report_id = c.report_id
            LEFT JOIN users u ON c.user_id = u.user_id
            WHERE r.status = ? AND r.severity LIKE ? AND r.assigned_to LIKE ? AND r.title LIKE ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind the parameters for status, severity, assigned_to, and search
        mysqli_stmt_bind_param($stmt, "ssss", $status, $severity, $assigned_to, $search);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch and return the results
        $reports = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reports[] = $row; // Add each report to the results array
        }

        mysqli_stmt_close($stmt);
        return $reports; // Return the array of reports
    } else {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        return []; // Return empty array if the query fails
    }
}

// Function to get all categories
function get_all_categories($conn) {
    // Check if the connection is valid
    if (!$conn) {
        return []; // Return empty array if no connection available
    }

    // Query to fetch all categories
    $sql = "SELECT * FROM categories";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        // Fetch all categories into an array
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row; // Add each category to the array
        }
        return $categories; // Return the categories array
    } else {
        error_log("Error fetching categories: " . mysqli_error($conn));
        return []; // Return empty array if the query fails
    }
}

// Function to delete a report
function delete_report($conn, $report_id) {
    if (!$conn) {
        return false; // Return false if no connection available
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete comments first (could be done with ON DELETE CASCADE, but this is safer)
        $sql = "DELETE FROM comments WHERE report_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $report_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Then delete the report
        $sql = "DELETE FROM reports WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $report_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        return $result;
    } catch (Exception $e) {
        // Rollback transaction if error
        mysqli_rollback($conn);
        return false;
    }
}

// Function to return status color
function get_status_color($status) {
    switch ($status) {
        case 'active':
            return 'success'; // Green
        case 'inactive':
            return 'warning'; // Yellow
        case 'banned':
            return 'danger'; // Red
        default:
            return 'secondary'; // Grey for unknown
    }
}

// Function to return priority color
function get_priority_color($priority) {
    switch ($priority) {
        case 'high':
            return 'danger'; // Red
        case 'medium':
            return 'warning'; // Yellow
        case 'low':
            return 'success'; // Green
        default:
            return 'secondary'; // Grey for unknown
    }
}

// Function to get status color for users
function get_status_color_user($status) {
    switch ($status) {
        case 'active':
            return 'success'; // Green for active
        case 'inactive':
            return 'warning'; // Yellow for inactive
        case 'banned':
            return 'danger'; // Red for banned
        default:
            return 'default'; // Grey for unknown status
    }
}

// Function to get status color for role
function get_role_color_user($role) {
    switch ($role) {
        case 'admin':
            return 'danger';  // Red for admin
        case 'researcher':
            return 'info';  // Light blue for researcher
        case 'user':
            return 'primary';  // Blue for regular user
        default:
            return 'default';  // Grey for unknown roles
    }
}

// Function to format date
function format_date($date_string) {
    $date = new DateTime($date_string);
    return $date->format('M j, Y g:i A');
}

// Function to get filtered users
function get_filtered_users($conn, $search = '', $role = '', $status = '') {
    if (!$conn) {
        return []; // Return empty array if no connection available
    }
    
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = array();
    $types = "";
    
    if (!empty($search)) {
        $sql .= " AND (username LIKE ? OR email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    if (!empty($role)) {
        $sql .= " AND role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $users;
}
?>