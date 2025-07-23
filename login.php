<?php
// login.php
require_once 'includes/header.php';

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$username = $password = "";
$error = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty($_POST["username"])) {
        $error = "Username is required";
    } else {
        $username = clean_input($_POST["username"]);
    }
    
    // Validate password
    if (empty($_POST["password"])) {
        $error = "Password is required";
    } else {
        $password = $_POST["password"]; // Don't clean password input
    }
    
    // Attempt login if no errors
    if (empty($error)) {
        $result = login_user($conn, $username, $password);
        
        if (isset($result["success"])) {
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result["error"];
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                
                <hr>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>