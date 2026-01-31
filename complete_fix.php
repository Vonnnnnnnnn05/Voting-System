<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

$db = getDB();

echo "<!DOCTYPE html><html><head><title>Complete Fix</title>
<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border: 2px solid #ddd; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.fixed { background: #d4edda; border-color: #28a745; }
h2 { color: #333; }
</style>
</head><body>";

echo "<h1>🔧 Complete Voting System Fix</h1>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

$fixCount = 0;

// FIX 1: Verify Current User
echo "<div class='box'>";
echo "<h2>Step 1: User Verification</h2>";
if (isLoggedIn()) {
    echo "<p class='success'>✓ You are logged in as: {$_SESSION['user_email']}</p>";
    
    $stmt = $db->prepare("SELECT is_verified FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user['is_verified']) {
        echo "<p class='error'>✗ Your email is NOT verified</p>";
        
        // AUTO-FIX: Verify the user
        $db->prepare("UPDATE users SET is_verified = 1 WHERE id = :id")
           ->execute([':id' => $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['user_verified'] = true;
        
        echo "<p class='success'>✓ FIXED! Your email is now VERIFIED!</p>";
        $fixCount++;
    } else {
        echo "<p class='success'>✓ Your email is already verified</p>";
    }
} else {
    echo "<p class='error'>✗ You are not logged in</p>";
    echo "<p><a href='auth/login.php'>Click here to login</a></p>";
}
echo "</div>";

// FIX 2: Fix All Elections
echo "<div class='box'>";
echo "<h2>Step 2: Fix Election Times</h2>";

$stmt = $db->query("
    SELECT id, title, start_date, end_date,
    NOW() as server_time,
    TIMESTAMPDIFF(SECOND, start_date, NOW()) as seconds_since_start,
    TIMESTAMPDIFF(SECOND, NOW(), end_date) as seconds_until_end
    FROM elections
    ORDER BY created_at DESC
");
$elections = $stmt->fetchAll();

if (empty($elections)) {
    echo "<p class='warning'>⚠ No elections found</p>";
} else {
    foreach ($elections as $election) {
        echo "<h3>" . htmlspecialchars($election['title']) . " (ID: {$election['id']})</h3>";
        echo "<p>Start: {$election['start_date']}</p>";
        echo "<p>End: {$election['end_date']}</p>";
        echo "<p>Server: {$election['server_time']}</p>";
        
        // Check status using PHP logic (same as getElectionStatus)
        $now = time();
        $start = strtotime($election['start_date']);
        $end = strtotime($election['end_date']);
        
        if ($now < $start) {
            $status = 'upcoming';
        } elseif ($now > $end) {
            $status = 'ended';
        } else {
            $status = 'active';
        }
        
        echo "<p>Status: <strong>{$status}</strong></p>";
        
        if ($status !== 'active') {
            // Fix it!
            $newStart = date('Y-m-d H:i:s', strtotime('-10 minutes'));
            $newEnd = date('Y-m-d H:i:s', strtotime('+3 hours'));
            
            $updateStmt = $db->prepare("
                UPDATE elections 
                SET start_date = :start, end_date = :end
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':start' => $newStart,
                ':end' => $newEnd,
                ':id' => $election['id']
            ]);
            
            echo "<p class='success'>✓ FIXED! New times:</p>";
            echo "<p>New Start: {$newStart}</p>";
            echo "<p>New End: {$newEnd}</p>";
            
            // Verify the fix
            $verifyStart = strtotime($newStart);
            $verifyEnd = strtotime($newEnd);
            $verifyNow = time();
            
            if ($verifyNow >= $verifyStart && $verifyNow <= $verifyEnd) {
                echo "<p class='success'>✓✓ VERIFIED: Election is now ACTIVE!</p>";
            } else {
                echo "<p class='error'>✗ Something is wrong with time calculations</p>";
                echo "<p>Now timestamp: {$verifyNow}</p>";
                echo "<p>Start timestamp: {$verifyStart}</p>";
                echo "<p>End timestamp: {$verifyEnd}</p>";
            }
            
            $fixCount++;
        } else {
            echo "<p class='success'>✓ Already active</p>";
        }
        
        // Check candidates
        $candStmt = $db->prepare("SELECT COUNT(*) as count FROM candidates WHERE election_id = :id");
        $candStmt->execute([':id' => $election['id']]);
        $candCount = $candStmt->fetch()['count'];
        
        echo "<p>Candidates: {$candCount}</p>";
        if ($candCount == 0) {
            echo "<p class='error'>✗ No candidates! Add candidates to enable voting.</p>";
        }
        
        echo "<hr>";
    }
}
echo "</div>";

// Summary
echo "<div class='box fixed'>";
echo "<h2>✅ Fix Complete!</h2>";
echo "<p class='success'>Applied {$fixCount} fixes</p>";

if (isLoggedIn() && isVerified()) {
    echo "<h3>You are ready to vote!</h3>";
    echo "<p><a href='voter/elections.php' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;font-size:18px;'>🗳️ GO TO ELECTIONS PAGE</a></p>";
} else {
    echo "<p class='warning'>Complete the steps above first</p>";
}

echo "<p style='margin-top:20px;'><a href='fix_voting.php'>Run Full Diagnostics</a></p>";
echo "</div>";

echo "</body></html>";
?>
