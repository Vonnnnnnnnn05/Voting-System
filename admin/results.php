<?php
require_once '../config/config.php';
requireAdmin();

$db = getDB();

// Get election ID
$electionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

// Fetch voting results
$stmt = $db->prepare("
    SELECT c.id, c.name, c.description, c.photo,
           COUNT(v.id) as vote_count
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE c.election_id = :election_id
    GROUP BY c.id
    ORDER BY vote_count DESC, c.name ASC
");
$stmt->execute([':election_id' => $electionId]);
$results = $stmt->fetchAll();

// Calculate total votes
$totalVotes = array_sum(array_column($results, 'vote_count'));

// Get total registered voters
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'voter' AND is_verified = 1");
$totalVoters = $stmt->fetch()['total'];

// Calculate voter turnout
$voterTurnout = $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 2) : 0;

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Election Results</h2>
            <h5 class="text-muted"><?= htmlspecialchars($election['title']) ?></h5>
            <hr>
            <a href="elections.php" class="btn btn-secondary mb-3">← Back to Elections</a>
        </div>
    </div>
    
    <!-- Election Info -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Votes</h6>
                    <h3><?= $totalVotes ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Voters</h6>
                    <h3><?= $totalVoters ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Voter Turnout</h6>
                    <h3><?= $voterTurnout ?>%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Status</h6>
                    <h3><?= ucfirst(getElectionStatus($election['start_date'], $election['end_date'])) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Results Chart -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Vote Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="resultsChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Results Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Detailed Results</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($results)): ?>
                        <p class="text-muted">No candidates in this election.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Candidate</th>
                                        <th>Description</th>
                                        <th>Votes</th>
                                        <th>Percentage</th>
                                        <th>Visual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($results as $result): 
                                        $percentage = $totalVotes > 0 ? round(($result['vote_count'] / $totalVotes) * 100, 2) : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($rank === 1 && $result['vote_count'] > 0): ?>
                                                    <span class="badge bg-success">🏆 #<?= $rank ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">#<?= $rank ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($result['name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($result['description']) ?></td>
                                            <td><strong><?= $result['vote_count'] ?></strong></td>
                                            <td><?= $percentage ?>%</td>
                                            <td>
                                                <div class="progress" style="min-width: 200px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?= $percentage ?>%"
                                                         aria-valuenow="<?= $percentage ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        $rank++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Fetch results data and render chart
fetch('<?= APP_URL ?>/api/results.php?election_id=<?= $electionId ?>')
    .then(response => response.json())
    .then(data => {
        const ctx = document.getElementById('resultsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Votes',
                    data: data.votes,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Votes per Candidate'
                    }
                }
            }
        });
    })
    .catch(error => console.error('Error fetching results:', error));
</script>

<?php include '../views/footer.php'; ?>
