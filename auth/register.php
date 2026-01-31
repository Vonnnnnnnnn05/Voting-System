<?php
require_once '../config/config.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? '/admin/dashboard.php' : '/voter/elections.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = sanitize($_POST['full_name'] ?? '');
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($fullName)) {
        $error = 'All fields are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($email, $password, $fullName);
        
        if ($result['success']) {
            $success = $result['message'];
            // Clear form
            $email = $fullName = '';
        } else {
            $error = $result['message'];
        }
    }
}

include '../views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <p class="text-center">
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        </p>
                    <?php else: ?>
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($fullName ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">
                                    Must be at least 8 characters with uppercase, lowercase, and numbers
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>
