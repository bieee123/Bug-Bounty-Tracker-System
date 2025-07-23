<?php
// /includes/db_connect.php
$db_host = 'localhost';      // Sesuaikan dengan host database Anda
$db_user = 'root';       // Sesuaikan dengan username database
$db_pass = '';       // Sesuaikan dengan password database
$db_name = 'bug_bounty_db';  // Sesuaikan dengan nama database Anda

// Buat koneksi
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Periksa koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset untuk keamanan
mysqli_set_charset($conn, "utf8");
?>