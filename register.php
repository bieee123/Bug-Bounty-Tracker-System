<?php
// register.php
require_once 'includes/header.php';

// Check if user is already logged in
// In register.php, after successful registration
if (isset($result["success"])) {
    // Auto login after registration
    $_SESSION['user_id'] = $result["user_id"];
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'researcher';
    
    // Set success message
    $_SESSION['success_message'] = "Registration successful! Welcome to Bug Bounty Tracking System.";
    
    // Redirect to dashboard
    header("Location: login.php");
    exit();
}

$username = $email = $full_name = "";
$error = "";

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty($_POST["username"])) {
        $error = "Username is required";
    } else {
        $username = clean_input($_POST["username"]);
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            $error = "Username can only contain letters, numbers, and underscores";
        }
    }
    
    // Validate email
    if (empty($_POST["email"])) {
        $error = "Email is required";
    } else {
        $email = clean_input($_POST["email"]);
        if (!validate_email($email)) {
            $error = "Invalid email format";
        }
    }
    
    // Validate full name
    if (empty($_POST["full_name"])) {
        $error = "Full name is required";
    } else {
        $full_name = clean_input($_POST["full_name"]);
    }
    
    // Validate password
    if (empty($_POST["password"])) {
        $error = "Password is required";
    } elseif (strlen($_POST["password"]) < 8) {
        $error = "Password must be at least 8 characters long";
    }
    
    // Validate password confirmation
    if (empty($_POST["confirm_password"])) {
        $error = "Please confirm your password";
    } elseif ($_POST["password"] != $_POST["confirm_password"]) {
        $error = "Passwords do not match";
    }
    
    // Register user if no errors
    if (empty($error)) {
        $result = register_user($conn, $username, $email, $_POST["password"], $full_name, 'researcher');
        
        if (isset($result["success"])) {
            // Auto login after registration
            $_SESSION['user_id'] = $result["user_id"];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'researcher';
            
            // Set success message
            $_SESSION['success_message'] = "Registration successful! Welcome to Bug Bounty Tracking System.";
            
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
                <h4 class="mb-0">Register</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required>
                        <div class="form-text">Username can only contain letters, numbers, and underscores.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
                
                <hr>
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>