<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

$db = getDB();

echo "<!DOCTYPE html><html><head><title>Voting System Diagnostics</title>
<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9; }
</style>
</head><body>";

echo "<h1>🔧 Voting System Diagnostics & Auto-Fix</h1>";

// Check 1: User Status
echo "<div class='box'>";
echo "<h2>1. User Authentication</h2>";
if (isLoggedIn()) {
    echo "<p class='success'>✓ User is logged in</p>";
    echo "<p>User ID: {$_SESSION['user_id']}</p>";
    echo "<p>Email: {$_SESSION['user_email']}</p>";
    echo "<p>Name: {$_SESSION['user_name']}</p>";
    echo "<p>Role: {$_SESSION['user_role']}</p>";
    
    // Check verification
    $stmt = $db->prepare("SELECT is_verified FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user['is_verified']) {
        echo "<p class='success'>✓ Email is VERIFIED - Can vote</p>";
    } else {
        echo "<p class='error'>✗ Email is NOT VERIFIED - Cannot vote</p>";
        echo "<p><strong>Quick Fix:</strong> <a href='verify_email.php?auto=1'>Click here to verify now</a></p>";
    }
} else {
    echo "<p class='error'>✗ Not logged in</p>";
    echo "<p><a href='auth/login.php'>Login here</a></p>";
}
echo "</div>";

// Check 2: Elections Status
echo "<div class='box'>";
echo "<h2>2. Elections Status</h2>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

$stmt = $db->query("
    SELECT e.*, 
    NOW() as db_time,
    (NOW() >= e.start_date AND NOW() <= e.end_date) as is_active_now,
    (SELECT COUNT(*) FROM candidates WHERE election_id = e.id) as candidate_count
    FROM elections e
    ORDER BY e.created_at DESC
");
$elections = $stmt->fetchAll();

if (empty($elections)) {
    echo "<p class='warning'>⚠ No elections created yet</p>";
    if (isAdmin()) {
        echo "<p><a href='admin/elections.php'>Create an election</a></p>";
    }
} else {
    foreach ($elections as $election) {
        $status = getElectionStatus($election['start_date'], $election['end_date']);
        $statusClass = $status === 'active' ? 'success' : 'warning';
        
        echo "<hr>";
        echo "<h3>" . htmlspecialchars($election['title']) . "</h3>";
        echo "<p><strong>Status:</strong> <span class='{$statusClass}'>" . strtoupper($status) . "</span></p>";
        echo "<p>Start: {$election['start_date']}</p>";
        echo "<p>End: {$election['end_date']}</p>";
        echo "<p>DB Time: {$election['db_time']}</p>";
        echo "<p>Is Active (DB Check): " . ($election['is_active_now'] ? 'YES' : 'NO') . "</p>";
        echo "<p>Candidates: {$election['candidate_count']}</p>";
        
        if ($election['candidate_count'] == 0) {
            echo "<p class='error'>✗ No candidates added yet!</p>";
            if (isAdmin()) {
                echo "<p><a href='admin/candidates.php?election_id={$election['id']}'>Add candidates</a></p>";
            }
        }
        
        if ($status !== 'active') {
            echo "<p class='error'>✗ Election is not active (Status: {$status})</p>";
            if (isAdmin()) {
                echo "<p><strong>Quick Fix:</strong> <a href='admin/edit_times.php'>Fix election times</a></p>";
            }
        } else {
            echo "<p class='success'>✓ Election is ACTIVE and ready for voting!</p>";
        }
        
        // Check if user already voted
        if (isLoggedIn()) {
            $stmt = $db->prepare("SELECT id FROM votes WHERE election_id = :eid AND user_id = :uid");
            $stmt->execute([':eid' => $election['id'], ':uid' => $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo "<p class='success'>✓ You have already voted in this election</p>";
            } else {
                echo "<p>You have NOT voted yet</p>";
            }
        }
    }
}
echo "</div>";

// Check 3: Quick Actions
echo "<div class='box'>";
echo "<h2>3. Quick Actions</h2>";

if (isLoggedIn()) {
    if (!isVerified()) {
        echo "<p><a href='verify_email.php?auto=1' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>✓ Verify My Email</a></p>";
    }
    
    if (isAdmin()) {
        echo "<p><a href='admin/edit_times.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>⏰ Fix Election Times</a></p>";
        echo "<p><a href='admin/users.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>👥 Manage Voters</a></p>";
    }
    
    echo "<p><a href='voter/elections.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>🗳️ Go to Elections Page</a></p>";
} else {
    echo "<p><a href='auth/login.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Login</a></p>";
}
echo "</div>";

// Check 4: System Status
echo "<div class='box'>";
echo "<h2>4. System Status</h2>";

// Check database tables
$tables = ['users', 'elections', 'candidates', 'votes', 'audit_logs'];
foreach ($tables as $table) {
    $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✓ Table '{$table}': {$count} records</p>";
}

echo "</div>";

echo "<hr>";
echo "<h2>Summary & Recommendations</h2>";

$issues = [];
$canVote = true;

if (!isLoggedIn()) {
    $issues[] = "User is not logged in";
    $canVote = false;
} else {
    if (!isVerified()) {
        $issues[] = "User email is not verified";
        $canVote = false;
    }
}

$hasActiveElection = false;
foreach ($elections as $election) {
    if (getElectionStatus($election['start_date'], $election['end_date']) === 'active' && $election['candidate_count'] > 0) {
        $hasActiveElection = true;
        break;
    }
}

if (!$hasActiveElection) {
    $issues[] = "No active elections with candidates available";
    $canVote = false;
}

if (empty($issues)) {
    echo "<p class='success' style='font-size:18px;'>✓✓✓ EVERYTHING IS READY! You can vote now!</p>";
    echo "<p><a href='voter/elections.php' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;font-size:18px;'>🗳️ GO VOTE NOW!</a></p>";
} else {
    echo "<p class='error' style='font-size:18px;'>✗ Issues preventing voting:</p>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li class='error'>{$issue}</li>";
    }
    echo "</ol>";
    echo "<p><strong>Follow the Quick Fix links above to resolve these issues.</strong></p>";
}

echo "</body></html>";
?>
