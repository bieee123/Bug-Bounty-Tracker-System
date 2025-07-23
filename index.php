<?php
// index.php
require_once 'includes/header.php';
?>

<div class="jumbotron bg-light p-5 rounded">
    <h1 class="display-4">Welcome to Bug Bounty Tracking System</h1>
    <p class="lead">A platform for reporting, tracking, and managing security vulnerabilities.</p>
    <hr class="my-4">
    <p>Join our program to help improve security by finding and reporting vulnerabilities.</p>
    <?php if (!is_logged_in()): ?>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="register.php" role="button">Register</a>
            <a class="btn btn-outline-primary" href="login.php" role="button">Login</a>
        </div>
    <?php else: ?>
        <a class="btn btn-primary" href="dashboard.php" role="button">Go to Dashboard</a>
    <?php endif; ?>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-bug me-2"></i>Find Vulnerabilities</h5>
                <p class="card-text">Discover security issues in our systems and report them through our platform.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-shield-alt me-2"></i>Improve Security</h5>
                <p class="card-text">Help us enhance our security posture by reporting potential weaknesses.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-award me-2"></i>Get Recognized</h5>
                <p class="card-text">Earn recognition for your contributions to our security program.</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>