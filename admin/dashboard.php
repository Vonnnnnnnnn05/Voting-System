<?php
require_once '../config/config.php';
requireAdmin();

// Get statistics
$db = getDB();

// Total users
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'voter'");
$totalVoters = $stmt->fetch()['total'];

// Total elections
$stmt = $db->query("SELECT COUNT(*) as total FROM elections");
$totalElections = $stmt->fetch()['total'];

// Active elections
$stmt = $db->query("SELECT COUNT(*) as total FROM elections WHERE NOW() BETWEEN start_date AND end_date");
$activeElections = $stmt->fetch()['total'];

// Total votes
$stmt = $db->query("SELECT COUNT(*) as total FROM votes");
$totalVotes = $stmt->fetch()['total'];

// Recent elections
$stmt = $db->query("
    SELECT e.*, u.full_name as creator_name,
           (SELECT COUNT(*) FROM votes WHERE election_id = e.id) as vote_count,
           (SELECT COUNT(*) FROM candidates WHERE election_id = e.id) as candidate_count
    FROM elections e
    JOIN users u ON e.created_by = u.id
    ORDER BY e.created_at DESC
    LIMIT 5
");
$recentElections = $stmt->fetchAll();

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Admin Dashboard</h2>
            <hr>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Voters</h5>
                    <h2><?= $totalVoters ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Elections</h5>
                    <h2><?= $totalElections ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Active Elections</h5>
                    <h2><?= $activeElections ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Votes</h5>
                    <h2><?= $totalVotes ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Elections -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Recent Elections</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentElections)): ?>
                        <p class="text-muted">No elections created yet.</p>
                        <a href="elections.php" class="btn btn-primary">Create First Election</a>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Candidates</th>
                                        <th>Votes</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentElections as $election): ?>
                                        <?php $status = getElectionStatus($election['start_date'], $election['end_date']); ?>
                                        <tr>
                                            <td><?= htmlspecialchars($election['title']) ?></td>
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
                                            <td><?= htmlspecialchars($election['creator_name']) ?></td>
                                            <td>
                                                <a href="results.php?id=<?= $election['id'] ?>" 
                                                   class="btn btn-sm btn-info">View Results</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="elections.php" class="btn btn-primary">Manage Elections</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?>
