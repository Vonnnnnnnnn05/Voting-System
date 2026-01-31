<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Handle election creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCSRFToken();
    
    if ($_POST['action'] === 'create') {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        
        if (empty($title) || empty($startDate) || empty($endDate)) {
            $error = 'Title, start date, and end date are required.';
        } elseif (strtotime($startDate) >= strtotime($endDate)) {
            $error = 'End date must be after start date.';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO elections (title, description, start_date, end_date, created_by)
                    VALUES (:title, :description, :start_date, :end_date, :created_by)
                ");
                
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':start_date' => $startDate,
                    ':end_date' => $endDate,
                    ':created_by' => $_SESSION['user_id']
                ]);
                
                logAudit('election_created', "Created election: {$title}");
                setFlash('success', 'Election created successfully!');
                redirect('/admin/elections.php');
            } catch (PDOException $e) {
                $error = 'Failed to create election.';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $electionId = (int)$_POST['election_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM elections WHERE id = :id");
            $stmt->execute([':id' => $electionId]);
            
            logAudit('election_deleted', "Deleted election ID: {$electionId}");
            setFlash('success', 'Election deleted successfully!');
            redirect('/admin/elections.php');
        } catch (PDOException $e) {
            $error = 'Failed to delete election.';
        }
    }
}

// Fetch all elections
$stmt = $db->query("
    SELECT e.*, u.full_name as creator_name,
           (SELECT COUNT(*) FROM candidates WHERE election_id = e.id) as candidate_count,
           (SELECT COUNT(*) FROM votes WHERE election_id = e.id) as vote_count
    FROM elections e
    JOIN users u ON e.created_by = u.id
    ORDER BY e.created_at DESC
");
$elections = $stmt->fetchAll();

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Manage Elections</h2>
            <hr>
        </div>
    </div>
    
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
    
    <!-- Create Election Form -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Create New Election</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Election Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="start_date" 
                                           name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="end_date" 
                                           name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Election</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Elections List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Elections List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($elections)): ?>
                        <p class="text-muted">No elections created yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Candidates</th>
                                        <th>Votes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($elections as $election): ?>
                                        <?php $status = getElectionStatus($election['start_date'], $election['end_date']); ?>
                                        <tr>
                                            <td><?= $election['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($election['title']) ?></strong><br>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($election['description']) ?>
                                                </small>
                                            </td>
                                            <td><?= formatDate($election['start_date']) ?></td>
                                            <td><?= formatDate($election['end_date']) ?></td>
                                            <td>
                                                <?php if ($status === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($status === 'upcoming'): ?>
                                                    <span class="badge bg-warning">Upcoming</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Ended</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $election['candidate_count'] ?></td>
                                            <td><?= $election['vote_count'] ?></td>
                                            <td>
                                                <a href="candidates.php?election_id=<?= $election['id'] ?>" 
                                                   class="btn btn-sm btn-primary">Manage Candidates</a>
                                                <a href="results.php?id=<?= $election['id'] ?>" 
                                                   class="btn btn-sm btn-info">Results</a>
                                                
                                                <form method="POST" style="display:inline;" 
                                                      onsubmit="return confirm('Delete this election and all associated data?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="election_id" value="<?= $election['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
