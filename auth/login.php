<?php
require_once '../config/config.php';

$error = '';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? '/admin/dashboard.php' : '/voter/elections.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            setFlash('success', 'Welcome back!');
            redirect($result['role'] === 'admin' ? '/admin/dashboard.php' : '/voter/elections.php');
        } else {
            $error = $result['message'];
        }
    }
}

include '../views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php 
                    $flash = getFlash();
                    if ($flash): 
                    ?>
                        <div class="alert alert-<?= $flash['type'] ?>">
                            <?= htmlspecialchars($flash['message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($email ?? '') ?>" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                    
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            <strong>Default Admin:</strong> admin@votingsystem.com / admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>
