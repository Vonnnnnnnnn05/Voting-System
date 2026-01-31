<?php
require_once '../config/config.php';
requireLogin();

// Note: requireVerified() now allows access but shows warning
requireVerified();

$db = getDB();

// Fetch all elections
$stmt = $db->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM candidates WHERE election_id = e.id) as candidate_count,
           (SELECT COUNT(*) FROM votes WHERE election_id = e.id) as total_votes,
           (SELECT COUNT(*) FROM votes WHERE election_id = e.id AND user_id = {$_SESSION['user_id']}) as user_voted
    FROM elections e
    ORDER BY 
        CASE 
            WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 1
            WHEN NOW() < e.start_date THEN 2
            ELSE 3
        END,
        e.start_date DESC
");
$elections = $stmt->fetchAll();

include '../views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Available Elections</h2>
            <hr>
        </div>
    </div>
    
    <?php 
    $flash = getFlash();
    if ($flash): 
    ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!isVerified()): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-exclamation-triangle"></i> Email Verification Required</h5>
            <p>Your email address has not been verified yet. You can browse elections but cannot vote until you verify your email.</p>
            <p class="mb-0">Please check your email for the verification link, or contact support if you need assistance.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($elections)): ?>
        <div class="alert alert-info">
            <h5>No Elections Available</h5>
            <p>There are currently no elections available. Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($elections as $election): ?>
                <?php 
                $status = getElectionStatus($election['start_date'], $election['end_date']);
                $hasVoted = $election['user_voted'] > 0;
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 
                        <?= $status === 'active' ? 'border-success' : '' ?> 
                        <?= $status === 'ended' ? 'border-secondary' : '' ?>">
                        <div class="card-header 
                            <?= $status === 'active' ? 'bg-success text-white' : '' ?>
                            <?= $status === 'upcoming' ? 'bg-warning' : '' ?>
                            <?= $status === 'ended' ? 'bg-secondary text-white' : '' ?>">
                            <h5 class="mb-0">
                                <?= htmlspecialchars($election['title']) ?>
                                <?php if ($hasVoted): ?>
                                    <span class="badge bg-light text-dark float-end">
                                        <i class="fas fa-check"></i> Voted
                                    </span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?= htmlspecialchars($election['description']) ?></p>
                            
                            <div class="mb-2">
                                <strong>Status:</strong> 
                                <?php if ($status === 'active'): ?>
                                    <span class="badge bg-success">Active - Vote Now!</span>
                                <?php elseif ($status === 'upcoming'): ?>
                                    <span class="badge bg-warning">Upcoming</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Ended</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-2">
                                <strong><i class="far fa-calendar"></i> Start:</strong> 
                                <?= formatDate($election['start_date']) ?>
                            </div>
                            
                            <div class="mb-2">
                                <strong><i class="far fa-calendar"></i> End:</strong> 
                                <?= formatDate($election['end_date']) ?>
                            </div>
                            
                            <div class="mb-3">
                                <strong><i class="fas fa-users"></i> Candidates:</strong> 
                                <?= $election['candidate_count'] ?>
                                &nbsp;&nbsp;
                                <strong><i class="fas fa-vote-yea"></i> Total Votes:</strong> 
                                <?= $election['total_votes'] ?>
                            </div>
                            
                            <?php if ($status === 'active'): ?>
                                <?php if ($hasVoted): ?>
                                    <div class="alert alert-success mb-2">
                                        <i class="fas fa-check-circle"></i> You have already voted in this election.
                                    </div>
                                    <a href="vote.php?election_id=<?= $election['id'] ?>" 
                                       class="btn btn-info w-100">View Candidates</a>
                                <?php elseif (!isVerified()): ?>
                                    <div class="alert alert-warning mb-2">
                                        <i class="fas fa-exclamation-triangle"></i> Verify your email to vote
                                    </div>
                                    <button class="btn btn-secondary w-100" disabled>
                                        Email Verification Required
                                    </button>
                                <?php else: ?>
                                    <a href="vote.php?election_id=<?= $election['id'] ?>" 
                                       class="btn btn-success w-100">
                                        <i class="fas fa-vote-yea"></i> Vote Now
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($status === 'upcoming'): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    Voting Opens Soon
                                </button>
                            <?php else: ?>
                                <a href="vote.php?election_id=<?= $election['id'] ?>" 
                                   class="btn btn-secondary w-100">View Results</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../views/footer.php'; ?>
