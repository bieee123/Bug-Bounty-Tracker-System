<?php
// includes/file_functions.php

// Upload file to server
function upload_file($file) {
    // Create uploads directory if it doesn't exist
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file["name"]);
    $target_file = $upload_dir . $filename;
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "path" => $target_file];
    } else {
        return ["error" => "Failed to upload file."];
    }
}

// Validate email format
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Clean user input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>