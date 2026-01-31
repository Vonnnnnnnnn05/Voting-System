<?php
require_once '../config/config.php';

$message = '';
$type = 'info';

// Handle email verification
if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    if (verifyEmail($token)) {
        $message = 'Email verified successfully! You can now login and start voting.';
        $type = 'success';
    } else {
        $message = 'Invalid or expired verification token. Please contact support.';
        $type = 'error';
    }
}

include '../views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Email Verification</h4>
                </div>
                <div class="card-body text-center">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                        
                        <?php if ($type === 'success'): ?>
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-secondary">Register Again</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Please check your email for the verification link.</p>
                        <a href="<?= APP_URL ?>" class="btn btn-secondary">Go to Home</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>
