<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$message = '';
$type = 'info';

// Handle user verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCSRFToken();
    
    if ($_POST['action'] === 'verify') {
        $userId = (int)$_POST['user_id'];
        
        try {
            $stmt = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            
            logAudit('user_verified_by_admin', "Admin verified user ID: {$userId}");
            setFlash('success', 'User verified successfully!');
            redirect('/admin/users.php');
        } catch (PDOException $e) {
            $message = 'Verification failed: ' . $e->getMessage();
            $type = 'danger';
        }
    } elseif ($_POST['action'] === 'unverify') {
        $userId = (int)$_POST['user_id'];
        
        try {
            $stmt = $db->prepare("UPDATE users SET is_verified = 0 WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            
            logAudit('user_unverified_by_admin', "Admin unverified user ID: {$userId}");
            setFlash('success', 'User verification revoked!');
            redirect('/admin/users.php');
        } catch (PDOException $e) {
            $message = 'Action failed: ' . $e->getMessage();
            $type = 'danger';
        }
    } elseif ($_POST['action'] === 'delete') {
        $userId = (int)$_POST['user_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'voter'");
            $stmt->execute([':id' => $userId]);
            
            logAudit('user_deleted_by_admin', "Admin deleted user ID: {$userId}");
            setFlash('success', 'User deleted successfully!');
            redirect('/admin/users.php');
        } catch (PDOException $e) {
            $message = 'Delete failed: ' . $e->getMessage();
            $type = 'danger';
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Fetch users based on filter
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM votes WHERE user_id = u.id) as vote_count
        FROM users u
        WHERE role = 'voter'";

if ($filter === 'verified') {
    $sql .= " AND is_verified = 1";
} elseif ($filter === 'unverified') {
    $sql .= " AND is_verified = 0";
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->query($sql);
$users = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'voter'");
$totalVoters = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'voter' AND is_verified = 1");
$verifiedVoters = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'voter' AND is_verified = 0");
$unverifiedVoters = $stmt->fetch()['total'];

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Manage Voters</h2>
            <hr>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php 
    $flash = getFlash();
    if ($flash): 
    ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Voters</h5>
                    <h2><?= $totalVoters ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Verified Voters</h5>
                    <h2><?= $verifiedVoters ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Unverified Voters</h5>
                    <h2><?= $unverifiedVoters ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">
                    All Voters (<?= $totalVoters ?>)
                </a>
                <a href="?filter=verified" class="btn btn-<?= $filter === 'verified' ? 'success' : 'outline-success' ?>">
                    Verified (<?= $verifiedVoters ?>)
                </a>
                <a href="?filter=unverified" class="btn btn-<?= $filter === 'unverified' ? 'warning' : 'outline-warning' ?>">
                    Unverified (<?= $unverifiedVoters ?>)
                </a>
            </div>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Voters List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <p class="text-muted">No voters found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Votes Cast</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle"></i> Verified
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Unverified
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $user['vote_count'] ?></td>
                                            <td><?= formatDate($user['created_at'], 'M d, Y') ?></td>
                                            <td>
                                                <?php if (!$user['is_verified']): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="verify">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" 
                                                                title="Verify this user">
                                                            <i class="fas fa-check"></i> Verify
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="unverify">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" 
                                                                title="Revoke verification"
                                                                onclick="return confirm('Revoke verification for this user?');">
                                                            <i class="fas fa-times"></i> Unverify
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="display:inline;">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            title="Delete this user"
                                                            onclick="return confirm('Delete this user? All their votes will be removed.');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>
