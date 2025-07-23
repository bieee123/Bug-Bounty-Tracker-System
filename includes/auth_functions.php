<?php
// includes/auth_functions.php

// Register new user
function register_user($conn, $username, $email, $password, $full_name, $role = 'user') {
    // Check if username already exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return ["error" => "Username already exists. Please choose another username."];
    }
    
    // Check if email already exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return ["error" => "Email already registered. Please use another email or login."];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $hashed_password, $full_name, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        return ["success" => true, "user_id" => mysqli_insert_id($conn)];
    } else {
        return ["error" => "Registration failed. Please try again."];
    }
}

// Login user
function login_user($conn, $username, $password) {
    // Get user by username
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            return ["success" => true];
        } else {
            return ["error" => "Invalid password."];
        }
    } else {
        return ["error" => "Username not found."];
    }
}

// Get user by ID - Add this function
function get_user_by_id($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    } else {
        return null;
    }
}

// Update user profile
function update_profile($conn, $user_id, $full_name, $email, $profile_image = null) {
    $sql = "UPDATE users SET full_name = ?, email = ?";
    $types = "ss";
    $params = [$full_name, $email];
    
    if ($profile_image !== null) {
        $sql .= ", profile_image = ?";
        $types .= "s";
        $params[] = $profile_image;
    }
    
    $sql .= " WHERE user_id = ?";
    $types .= "i";
    $params[] = $user_id;
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        return ["success" => true];
    } else {
        return ["error" => "Failed to update profile. Please try again."];
    }
}

// Change password
function change_password($conn, $user_id, $current_password, $new_password) {
    // Get current user information
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                return ["success" => true];
            } else {
                return ["error" => "Failed to change password. Please try again."];
            }
        } else {
            return ["error" => "Current password is incorrect."];
        }
    } else {
        return ["error" => "User not found."];
    }
}

// Check if user has permission to edit report
function can_edit_report($conn, $user_id, $report_id) {
    // Get report information
    $report = get_report_by_id($conn, $report_id);
    
    if (!$report) {
        return false;
    }
    
    // Check if user is admin or report owner
    $user_info = get_user_by_id($conn, $user_id);
    
    return ($user_info['role'] == 'admin' || $report['reported_by'] == $user_id);
}
?>