<?php
require_once 'config/config.php';

$message = '';
$type = 'info';

// Auto-verify current logged in user
if (isset($_GET['auto']) && isLoggedIn()) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['is_verified'] = 1;
        
        $message = 'Your account has been verified successfully!';
        $type = 'success';
        
        logAudit('email_verified', 'User manually verified their email', $_SESSION['user_id']);
    } catch (PDOException $e) {
        $message = 'Verification failed: ' . $e->getMessage();
        $type = 'danger';
    }
}

// Manual verification by email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = sanitize($_POST['email']);
    
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Email '{$email}' has been verified successfully!";
            $type = 'success';
            
            // If it's the current user, update session
            if (isLoggedIn() && $_SESSION['user_email'] === $email) {
                $_SESSION['is_verified'] = 1;
            }
            
            logAudit('email_verified', "Email verified: {$email}");
        } else {
            $message = "No user found with email: {$email}";
            $type = 'warning';
        }
    } catch (PDOException $e) {
        $message = 'Verification failed: ' . $e->getMessage();
        $type = 'danger';
    }
}

// Get all unverified users
$unverifiedUsers = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, email, full_name, created_at FROM users WHERE is_verified = 0 ORDER BY created_at DESC");
    $unverifiedUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignore error
}

include 'views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle"></i> Email Verification Tool</h4>
                    <small>For local development - bypasses email sending</small>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn() && !isVerified()): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Your Account is Not Verified</h5>
                            <p>Click the button below to verify your account instantly:</p>
                            <a href="?auto=1" class="btn btn-success">
                                <i class="fas fa-check"></i> Verify My Account Now
                            </a>
                        </div>
                    <?php elseif (isLoggedIn() && isVerified()): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Your account is already verified!
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h5>Manual Verification by Email</h5>
                    <form method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="email" name="email" class="form-control" 
                                   placeholder="Enter email address to verify" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Verify Email
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($unverifiedUsers)): ?>
                        <hr>
                        <h5>Unverified Users (<?= count($unverifiedUsers) ?>)</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Name</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unverifiedUsers as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= formatDate($user['created_at']) ?></td>
                                            <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Verify
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> All users are verified!
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="text-center">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'voter/elections.php' ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        <?php else: ?>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Go to Home
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle"></i> About This Tool</h6>
                    <p class="mb-0 text-muted small">
                        This tool is for local development only. In production, users would receive 
                        an email with a verification link. Use this tool to manually verify accounts 
                        without setting up email services.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
