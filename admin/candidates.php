<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Get election ID
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if (!$electionId) {
    setFlash('error', 'Invalid election.');
    redirect('/admin/elections.php');
}

// Fetch election details
try {
    $stmt = $db->prepare("SELECT * FROM elections WHERE id = :id");
    $stmt->execute([':id' => $electionId]);
    $election = $stmt->fetch();
    
    if (!$election) {
        setFlash('error', 'Election not found.');
        redirect('/admin/elections.php');
    }
} catch (PDOException $e) {
    setFlash('error', 'Failed to fetch election.');
    redirect('/admin/elections.php');
}

// Handle candidate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCSRFToken();
    
    if ($_POST['action'] === 'create') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $photo = null;
        
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = uploadCandidatePhoto($_FILES['photo']);
            if (!$photo) {
                $error = 'Failed to upload photo.';
            }
        }
        
        if (empty($name)) {
            $error = 'Candidate name is required.';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO candidates (election_id, name, description, photo)
                    VALUES (:election_id, :name, :description, :photo)
                ");
                
                $stmt->execute([
                    ':election_id' => $electionId,
                    ':name' => $name,
                    ':description' => $description,
                    ':photo' => $photo
                ]);
                
                logAudit('candidate_created', "Created candidate: {$name} for election ID: {$electionId}");
                setFlash('success', 'Candidate added successfully!');
                redirect("/admin/candidates.php?election_id={$electionId}");
            } catch (PDOException $e) {
                $error = 'Failed to create candidate.';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $candidateId = (int)$_POST['candidate_id'];
        
        try {
            // Get candidate info for logging
            $stmt = $db->prepare("SELECT name, photo FROM candidates WHERE id = :id");
            $stmt->execute([':id' => $candidateId]);
            $candidate = $stmt->fetch();
            
            // Delete candidate
            $stmt = $db->prepare("DELETE FROM candidates WHERE id = :id AND election_id = :election_id");
            $stmt->execute([
                ':id' => $candidateId,
                ':election_id' => $electionId
            ]);
            
            // Delete photo file if exists
            if ($candidate && $candidate['photo']) {
                $photoPath = UPLOAD_PATH . $candidate['photo'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            logAudit('candidate_deleted', "Deleted candidate ID: {$candidateId}");
            setFlash('success', 'Candidate deleted successfully!');
            redirect("/admin/candidates.php?election_id={$electionId}");
        } catch (PDOException $e) {
            $error = 'Failed to delete candidate.';
        }
    }
}

// Fetch candidates for this election
$stmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM votes WHERE candidate_id = c.id) as vote_count
    FROM candidates c
    WHERE c.election_id = :election_id
    ORDER BY c.created_at DESC
");
$stmt->execute([':election_id' => $electionId]);
$candidates = $stmt->fetchAll();

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Manage Candidates</h2>
            <h5 class="text-muted">Election: <?= htmlspecialchars($election['title']) ?></h5>
            <hr>
            <a href="elections.php" class="btn btn-secondary mb-3">← Back to Elections</a>
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
    
    <!-- Add Candidate Form -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Candidate</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Candidate Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" 
                                           name="description" placeholder="Position, party, etc.">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Photo (Optional)</label>
                                    <input type="file" class="form-control" id="photo" name="photo" 
                                           accept="image/jpeg,image/png,image/jpg">
                                    <small class="form-text text-muted">Max 2MB, JPG/PNG only</small>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Candidate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Candidates List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Candidates List (<?= count($candidates) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($candidates)): ?>
                        <p class="text-muted">No candidates added yet.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($candidates as $candidate): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <?php if ($candidate['photo']): ?>
                                            <img src="<?= APP_URL ?>/assets/images/candidates/<?= htmlspecialchars($candidate['photo']) ?>" 
                                                 class="card-img-top" alt="<?= htmlspecialchars($candidate['name']) ?>"
                                                 style="height: 200px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                 style="height: 200px;">
                                                <i class="fas fa-user fa-4x"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($candidate['name']) ?></h5>
                                            <p class="card-text text-muted">
                                                <?= htmlspecialchars($candidate['description']) ?>
                                            </p>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-vote-yea"></i> 
                                                    <?= $candidate['vote_count'] ?> votes
                                                </small>
                                            </p>
                                            
                                            <form method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('Delete this candidate?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="candidate_id" value="<?= $candidate['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>
