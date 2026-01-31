<?php
require_once '../config/config.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

// Get election ID
$electionId = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if (!$electionId) {
    setFlash('error', 'Invalid election.');
    redirect('/voter/elections.php');
}

// Fetch election details
try {
    $stmt = $db->prepare("SELECT * FROM elections WHERE id = :id");
    $stmt->execute([':id' => $electionId]);
    $election = $stmt->fetch();
    
    if (!$election) {
        setFlash('error', 'Election not found.');
        redirect('/voter/elections.php');
    }
} catch (PDOException $e) {
    setFlash('error', 'Failed to fetch election.');
    redirect('/voter/elections.php');
}

// Check if user already voted
$stmt = $db->prepare("SELECT id FROM votes WHERE election_id = :election_id AND user_id = :user_id");
$stmt->execute([
    ':election_id' => $electionId,
    ':user_id' => $_SESSION['user_id']
]);
$hasVoted = $stmt->fetch() !== false;

// Get election status
$status = getElectionStatus($election['start_date'], $election['end_date']);

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])) {
    verifyCSRFToken();
    
    $candidateId = (int)$_POST['candidate_id'];
    
    // Check if user is verified
    if (!isVerified()) {
        $error = 'You must verify your email address before voting.';
    } elseif ($status !== 'active') {
        $error = 'This election is not currently active.';
    } elseif ($hasVoted) {
        $error = 'You have already voted in this election.';
    } else {
        try {
            // Verify candidate belongs to this election
            $stmt = $db->prepare("SELECT id FROM candidates WHERE id = :id AND election_id = :election_id");
            $stmt->execute([
                ':id' => $candidateId,
                ':election_id' => $electionId
            ]);
            
            if (!$stmt->fetch()) {
                $error = 'Invalid candidate selection.';
            } else {
                // Insert vote (unique constraint prevents double voting)
                $stmt = $db->prepare("
                    INSERT INTO votes (election_id, candidate_id, user_id)
                    VALUES (:election_id, :candidate_id, :user_id)
                ");
                
                $stmt->execute([
                    ':election_id' => $electionId,
                    ':candidate_id' => $candidateId,
                    ':user_id' => $_SESSION['user_id']
                ]);
                
                // Log vote
                logAudit('vote_cast', "Vote cast in election ID: {$electionId}");
                
                setFlash('success', 'Your vote has been recorded successfully! Thank you for participating.');
                redirect('/voter/elections.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error = 'You have already voted in this election.';
            } else {
                error_log("Vote submission failed: " . $e->getMessage());
                $error = 'Failed to submit vote. Please try again.';
            }
        }
    }
}

// Fetch candidates
$stmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM votes WHERE candidate_id = c.id) as vote_count
    FROM candidates c
    WHERE c.election_id = :election_id
    ORDER BY c.name ASC
");
$stmt->execute([':election_id' => $electionId]);
$candidates = $stmt->fetchAll();

// Calculate total votes for percentages
$totalVotes = array_sum(array_column($candidates, 'vote_count'));

include '../views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2><?= htmlspecialchars($election['title']) ?></h2>
            <p class="lead"><?= htmlspecialchars($election['description']) ?></p>
            <hr>
            <a href="elections.php" class="btn btn-secondary mb-3">← Back to Elections</a>
        </div>
    </div>
    
    <!-- Election Status -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Status:</strong> 
                            <?php if ($status === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($status === 'upcoming'): ?>
                                <span class="badge bg-warning">Upcoming</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Ended</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Start:</strong> <?= formatDate($election['start_date']) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>End:</strong> <?= formatDate($election['end_date']) ?>
                        </div>
                    </div>
                    
                    <?php if ($hasVoted): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="fas fa-check-circle"></i> 
                            <strong>You have already voted in this election.</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <!-- Candidates -->
    <div class="row">
        <div class="col-md-12">
            <h4>Candidates</h4>
        </div>
        
        <?php if (empty($candidates)): ?>
            <div class="col-md-12">
                <div class="alert alert-info">No candidates available for this election.</div>
            </div>
        <?php else: ?>
            <?php foreach ($candidates as $candidate): ?>
                <?php 
                $percentage = $totalVotes > 0 ? round(($candidate['vote_count'] / $totalVotes) * 100, 2) : 0;
                $canVote = $status === 'active' && !$hasVoted;
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 <?= $canVote ? 'border-primary' : '' ?>">
                        <?php if ($candidate['photo']): ?>
                            <img src="<?= APP_URL ?>/assets/images/candidates/<?= htmlspecialchars($candidate['photo']) ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($candidate['name']) ?>"
                                 style="height: 250px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light text-secondary d-flex align-items-center justify-content-center" 
                                 style="height: 250px;">
                                <i class="fas fa-user fa-5x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($candidate['name']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($candidate['description']) ?></p>
                            
                            <?php if ($status === 'ended'): ?>
                                <div class="mb-2">
                                    <strong>Votes:</strong> <?= $candidate['vote_count'] ?> 
                                    (<?= $percentage ?>%)
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $percentage ?>%"
                                         aria-valuenow="<?= $percentage ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($canVote): ?>
                                <form method="POST" action="" 
                                      onsubmit="return confirm('Confirm your vote for <?= htmlspecialchars($candidate['name']) ?>? This action cannot be undone.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="candidate_id" value="<?= $candidate['id'] ?>">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-vote-yea"></i> Vote for <?= htmlspecialchars($candidate['name']) ?>
                                    </button>
                                </form>
                            <?php elseif ($status === 'upcoming'): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    Voting Not Started
                                </button>
                            <?php elseif ($hasVoted): ?>
                                <button class="btn btn-success w-100" disabled>
                                    <i class="fas fa-check"></i> You Have Voted
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../views/footer.php'; ?>
