<?php
require_once 'config/config.php';

// If logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/voter/elections.php');
    }
}

include 'views/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="row mt-5">
        <div class="col-md-12 text-center">
            <h1 class="display-3 mb-4">
                <i class="fas fa-vote-yea text-primary"></i>
                Welcome to <?= APP_NAME ?>
            </h1>
            <p class="lead text-muted mb-5">
                Secure, Transparent, and Convenient Online Voting Platform
            </p>
        </div>
    </div>
    
    <!-- Features -->
    <div class="row mb-5">
        <div class="col-md-4 text-center mb-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-shield-alt fa-4x text-primary"></i>
                    </div>
                    <h4>Secure Voting</h4>
                    <p class="text-muted">
                        Industry-standard encryption and security measures protect your vote
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 text-center mb-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-user-check fa-4x text-success"></i>
                    </div>
                    <h4>Easy to Use</h4>
                    <p class="text-muted">
                        Simple and intuitive interface for voters of all technical levels
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 text-center mb-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-chart-bar fa-4x text-info"></i>
                    </div>
                    <h4>Real-time Results</h4>
                    <p class="text-muted">
                        View election results instantly with interactive charts
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How It Works -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h2 class="text-center mb-4">How It Works</h2>
        </div>
        <div class="col-md-3 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px;">
                    <h3 class="mb-0">1</h3>
                </div>
            </div>
            <h5>Register</h5>
            <p class="text-muted">Create your account with email verification</p>
        </div>
        <div class="col-md-3 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px;">
                    <h3 class="mb-0">2</h3>
                </div>
            </div>
            <h5>Verify Email</h5>
            <p class="text-muted">Confirm your email address to activate voting</p>
        </div>
        <div class="col-md-3 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px;">
                    <h3 class="mb-0">3</h3>
                </div>
            </div>
            <h5>Browse Elections</h5>
            <p class="text-muted">View available elections and candidates</p>
        </div>
        <div class="col-md-3 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px;">
                    <h3 class="mb-0">4</h3>
                </div>
            </div>
            <h5>Cast Your Vote</h5>
            <p class="text-muted">Vote securely for your preferred candidate</p>
        </div>
    </div>
    
    <!-- CTA Section -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card bg-primary text-white text-center">
                <div class="card-body p-5">
                    <h2 class="mb-4">Ready to Get Started?</h2>
                    <p class="lead mb-4">Join thousands of voters using our secure platform</p>
                    <div>
                        <a href="auth/register.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-user-plus"></i> Register Now
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Security Info -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Security Features</h5>
                <ul class="mb-0">
                    <li>Email verification required before voting</li>
                    <li>One vote per user per election (enforced at database level)</li>
                    <li>CSRF protection on all forms</li>
                    <li>Password hashing using industry standards</li>
                    <li>Complete audit trail of all actions</li>
                    <li>SQL injection prevention using prepared statements</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
