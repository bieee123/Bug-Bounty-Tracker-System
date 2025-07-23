<?php
// profile.php
require_once 'includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = get_user_by_id($conn, $user_id);

$error = "";
$success = "";

// Process profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_profile"])) {
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
        
        // Handle profile image upload
        $profile_image = null;
        if (!empty($_FILES["profile_image"]["name"])) {
            $allowed_types = ["image/jpeg", "image/jpg", "image/png"];
            if (!in_array($_FILES["profile_image"]["type"], $allowed_types)) {
                $error = "Only JPEG and PNG images are allowed";
            } elseif ($_FILES["profile_image"]["size"] > 2000000) { // 2MB limit
                $error = "Image size must be less than 2MB";
            } else {
                $upload_result = upload_file($_FILES["profile_image"]);
                if (isset($upload_result["error"])) {
                    $error = $upload_result["error"];
                } else {
                    $profile_image = $upload_result["path"];
                }
            }
        }
        
        // Update profile if no errors
        if (empty($error)) {
            $result = update_profile($conn, $user_id, $full_name, $email, $profile_image);
            
            if (isset($result["success"])) {
                $success = "Profile updated successfully";
                // Refresh user data
                $user = get_user_by_id($conn, $user_id);
            } else {
                $error = $result["error"];
            }
        }
    } elseif (isset($_POST["change_password"])) {
        // Validate current password
        if (empty($_POST["current_password"])) {
            $error = "Current password is required";
        }
        
        // Validate new password
        if (empty($_POST["new_password"])) {
            $error = "New password is required";
        } elseif (strlen($_POST["new_password"]) < 8) {
            $error = "New password must be at least 8 characters long";
        }
        
        // Validate password confirmation
        if (empty($_POST["confirm_password"])) {
            $error = "Please confirm your new password";
        } elseif ($_POST["new_password"] != $_POST["confirm_password"]) {
            $error = "New passwords do not match";
        }
        
        // Change password if no errors
        if (empty($error)) {
            $result = change_password($conn, $user_id, $_POST["current_password"], $_POST["new_password"]);
            
            if (isset($result["success"])) {
                $success = "Password changed successfully";
            } else {
                $error = $result["error"];
            }
        }
    }
}
?>

<h2>My Profile</h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Profile Information</h5>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150" alt="Default Profile" class="img-fluid rounded-circle mb-3">
                <?php endif; ?>
                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                <p>
                    <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                </p>
                <p>
                    <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <p>
                    <i class="fas fa-calendar me-2"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Edit Profile</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image">
                        <div class="form-text">Upload a new profile image (JPEG, PNG, max 2MB)</div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Change Password</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>