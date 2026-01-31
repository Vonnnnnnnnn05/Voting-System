<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$message = '';
$type = 'info';

// Handle election time update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id'])) {
    verifyCSRFToken();
    
    $electionId = (int)$_POST['election_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    
    try {
        $stmt = $db->prepare("UPDATE elections SET start_date = :start_date, end_date = :end_date WHERE id = :id");
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':id' => $electionId
        ]);
        
        logAudit('election_times_updated', "Updated election ID: {$electionId}");
        setFlash('success', 'Election times updated successfully!');
        redirect('/admin/edit_times.php');
    } catch (PDOException $e) {
        $message = 'Update failed: ' . $e->getMessage();
        $type = 'danger';
    }
}

// Fetch all elections with current time
$stmt = $db->query("
    SELECT e.*, NOW() as server_time,
    CASE 
        WHEN NOW() < e.start_date THEN 'upcoming'
        WHEN NOW() > e.end_date THEN 'ended'
        ELSE 'active'
    END as calculated_status
    FROM elections e
    ORDER BY e.start_date DESC
");
$elections = $stmt->fetchAll();

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Edit Election Times</h2>
            <p class="text-muted">Adjust start and end times to make elections active</p>
            <hr>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php 
    $flash = getFlash();
    if ($flash): 
    ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    
    <div class="alert alert-info">
        <strong><i class="fas fa-clock"></i> Current Server Time:</strong> 
        <?= date('Y-m-d H:i:s') ?> (<?= date_default_timezone_get() ?>)
    </div>
    
    <?php if (empty($elections)): ?>
        <div class="alert alert-warning">No elections found. <a href="elections.php">Create an election first</a>.</div>
    <?php else: ?>
        <?php foreach ($elections as $election): ?>
            <div class="card mb-3">
                <div class="card-header bg-<?= $election['calculated_status'] === 'active' ? 'success' : ($election['calculated_status'] === 'upcoming' ? 'warning' : 'secondary') ?> text-white">
                    <h5 class="mb-0">
                        <?= htmlspecialchars($election['title']) ?>
                        <span class="badge bg-light text-dark float-end">
                            <?= strtoupper($election['calculated_status']) ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Current Times:</h6>
                            <p>
                                <strong>Start:</strong> <?= $election['start_date'] ?><br>
                                <strong>End:</strong> <?= $election['end_date'] ?><br>
                                <strong>Server Now:</strong> <?= $election['server_time'] ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Update Times:</h6>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="election_id" value="<?= $election['id'] ?>">
                                
                                <div class="mb-2">
                                    <label class="form-label small">Start Date & Time</label>
                                    <input type="datetime-local" name="start_date" class="form-control form-control-sm" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($election['start_date'])) ?>" required>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label small">End Date & Time</label>
                                    <input type="datetime-local" name="end_date" class="form-control form-control-sm" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($election['end_date'])) ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Update Times
                                </button>
                                
                                <button type="button" class="btn btn-success btn-sm" 
                                        onclick="setActiveNow(<?= $election['id'] ?>)">
                                    <i class="fas fa-bolt"></i> Make Active Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function setActiveNow(electionId) {
    // Set start time to 5 minutes ago, end time to 2 hours from now
    const now = new Date();
    const start = new Date(now.getTime() - 5 * 60000); // 5 minutes ago
    const end = new Date(now.getTime() + 2 * 3600000); // 2 hours from now
    
    const form = document.querySelector(`input[name="election_id"][value="${electionId}"]`).closest('form');
    
    // Format for datetime-local input
    form.querySelector('input[name="start_date"]').value = formatDateTime(start);
    form.querySelector('input[name="end_date"]').value = formatDateTime(end);
}

function formatDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}
</script>

<?php include '../views/footer.php'; ?>
